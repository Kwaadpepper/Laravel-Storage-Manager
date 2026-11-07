<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Exception\IllegalDomainStateException;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\FileStream;

describe('FileStream Value Object', function (): void {
    it('creates a FileStream with a valid readable resource', function (): void {
        // GIVEN
        $resource = fopen('php://temp', 'r+');
        expect($resource)->not->toBeFalse();

        // WHEN
        $fileStream = new FileStream($resource);

        // THEN
        expect($fileStream->stream())->toBe($resource);
    });

    it('throws exception if not a valid resource', function (): void {
        // GIVEN
        $invalidResource = 'not-a-resource';

        // WHEN
        $action = fn (): FileStream => new FileStream($invalidResource);

        // THEN
        expect($action)
            ->toThrow(IllegalDomainStateException::class, 'The provided value is not a valid stream resource.');
    });

    it('throws exception if resource is not a stream', function (): void {
        // GIVEN
        $context = stream_context_create();

        // WHEN
        $action = fn (): FileStream => new FileStream($context);

        // THEN
        expect($action)
            ->toThrow(IllegalDomainStateException::class, 'The provided value is not a valid stream resource.');
    });

    it('throws exception if stream is not readable', function (): void {
        // GIVEN
        $tempFile = tempnam(sys_get_temp_dir(), 'fs_write_');
        expect($tempFile)->not->toBeFalse();
        $resource = fopen((string) $tempFile, 'w');
        expect($resource)->not->toBeFalse();

        try {
            // WHEN
            $action = fn (): FileStream => new FileStream($resource);

            // THEN
            expect($action)
                ->toThrow(IllegalDomainStateException::class, 'The stream is not readable.');
        } finally {
            if (is_resource($resource)) {
                fclose($resource);
            }
            if (file_exists((string) $tempFile)) {
                unlink((string) $tempFile);
            }
        }
    });

    it('creates a FileStream from local path via fromPath', function (): void {
        // GIVEN
        $tempFile = tempnam(sys_get_temp_dir(), 'fs_test_');
        expect($tempFile)->not->toBeFalse();
        file_put_contents((string) $tempFile, 'Sample content');

        try {
            // WHEN
            $stream = FileStream::fromPath((string) $tempFile);

            // THEN
            expect($stream->size())->toBe(14);
            expect($stream->getContents())->toBe('Sample content');
        } finally {
            if (file_exists((string) $tempFile)) {
                unlink((string) $tempFile);
            }
        }
    });

    it('throws IllegalDomainStateException in fromPath when file does not exist', function (): void {
        // GIVEN
        $path = '/non/existent/path/file.dat';

        // WHEN
        $action = fn (): FileStream => FileStream::fromPath($path);

        // THEN
        expect($action)
            ->toThrow(IllegalDomainStateException::class);
    });

    it('creates a FileStream from string via fromString', function (): void {
        // GIVEN
        $content = 'InMemory string content';

        // WHEN
        $stream = FileStream::fromString($content);

        // THEN
        expect($stream->size())->toBe(23);
        expect($stream->isSeekable())->toBeTrue();
        expect($stream->getContents())->toBe('InMemory string content');
    });

    it('throws exception when accessing stream() on a closed stream', function (): void {
        // GIVEN
        $resource = fopen('php://temp', 'r+');
        expect($resource)->not->toBeFalse();
        $fileStream = new FileStream($resource);
        fclose($resource);

        // WHEN
        $action = fn (): mixed => $fileStream->stream();

        // THEN
        expect($action)
            ->toThrow(IllegalDomainStateException::class, 'The stream resource is no longer valid.');
    });

    it('returns the correct size of the stream and 0 when closed', function (): void {
        // GIVEN
        $resource = fopen('php://temp', 'r+');
        expect($resource)->not->toBeFalse();
        fwrite($resource, 'Hello World!');
        $fileStream = new FileStream($resource);

        // WHEN
        $initialSize = $fileStream->size();
        $fileStream->close();
        $closedSize = $fileStream->size();

        // THEN
        expect($initialSize)->toBe(12);
        expect($closedSize)->toBe(0);
    });

    it('checks if stream is seekable', function (): void {
        // GIVEN
        $stream = FileStream::fromString('seekable test');

        // WHEN
        $isSeekable = $stream->isSeekable();

        // THEN
        expect($isSeekable)->toBeTrue();
    });

    it('checks eof status', function (): void {
        // GIVEN
        $stream = FileStream::fromString('ab');

        // WHEN
        $eofInitial = $stream->eof();
        $stream->read(2);
        $stream->read(1);
        $eofAfterReads = $stream->eof();

        $stream->close();
        $eofClosed = $stream->eof();

        // THEN
        expect($eofInitial)->toBeFalse();
        expect($eofAfterReads)->toBeTrue();
        expect($eofClosed)->toBeTrue();
    });

    it('seeks within a seekable stream and reads correctly', function (): void {
        // GIVEN
        $stream = FileStream::fromString('0123456789ABCDEF');

        // WHEN
        $stream->seek(5);
        $read = $stream->read(5);
        $stream->seek(0);
        $stream->seek(-1);

        // THEN
        expect($read)->toBe('56789');
    });

    it('seeks in a non-seekable stream by consuming bytes', function (): void {
        // GIVEN
        $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        expect($sockets)->not->toBeFalse();
        fwrite($sockets[1], '0123456789ABCDEF');
        fclose($sockets[1]);

        $stream = new FileStream($sockets[0]);
        expect($stream->isSeekable())->toBeFalse();

        // WHEN
        $stream->seek(5);
        $read = $stream->read(5);

        // THEN
        expect($read)->toBe('56789');
    });

    it('returns empty string when read called with non-positive length or closed stream', function (): void {
        // GIVEN
        $stream = FileStream::fromString('data');

        // WHEN
        $zeroRead     = $stream->read(0);
        $negativeRead = $stream->read(-5);
        $stream->close();
        $closedRead = $stream->read(4);

        // THEN
        expect($zeroRead)->toBe('');
        expect($negativeRead)->toBe('');
        expect($closedRead)->toBe('');
    });

    it('reads entire contents via getContents and rewinds seekable stream', function (): void {
        // GIVEN
        $stream = FileStream::fromString('Stream payload');

        // WHEN
        $stream->read(6);
        $contents      = $stream->getContents();
        $contentsAgain = $stream->getContents();

        // THEN
        expect($contents)->toBe('Stream payload');
        expect($contentsAgain)->toBe('Stream payload');
    });

    it('reads partial contents via getContents when maxBytes is specified', function (): void {
        // GIVEN
        $stream = FileStream::fromString('PrefixAndSuffix');

        // WHEN
        $contents = $stream->getContents(6);

        // THEN
        expect($contents)->toBe('Prefix');
    });

    it('returns empty string when getContents called on closed stream', function (): void {
        // GIVEN
        $stream = FileStream::fromString('test');
        $stream->close();

        // WHEN
        $contents = $stream->getContents();

        // THEN
        expect($contents)->toBe('');
    });

    it('copies stream contents to target path via copyToPath', function (): void {
        // GIVEN
        $stream   = FileStream::fromString('Copy destination content');
        $tempFile = tempnam(sys_get_temp_dir(), 'fs_copy_');
        expect($tempFile)->not->toBeFalse();

        // WHEN
        try {
            $stream->copyToPath((string) $tempFile);

            // THEN
            expect(file_get_contents((string) $tempFile))->toBe('Copy destination content');
        } finally {
            if (file_exists((string) $tempFile)) {
                unlink((string) $tempFile);
            }
        }
    });

    it('throws IllegalDomainStateException in copyToPath when stream is closed', function (): void {
        // GIVEN
        $stream   = FileStream::fromString('test');
        $tempFile = tempnam(sys_get_temp_dir(), 'fs_copy_');
        expect($tempFile)->not->toBeFalse();

        // WHEN
        $stream->close();
        $action = fn (): mixed => $stream->copyToPath((string) $tempFile);

        // THEN
        try {
            expect($action)->toThrow(IllegalDomainStateException::class);
        } finally {
            if (file_exists((string) $tempFile)) {
                unlink((string) $tempFile);
            }
        }
    });

    it('computes stream hash via hash method and rewinds stream', function (): void {
        // GIVEN
        $content = 'Hash content to verify';
        $stream  = FileStream::fromString($content);

        // WHEN
        $stream->read(5);
        $md5Hash    = $stream->hash('md5');
        $sha256Hash = $stream->hash('sha256');

        // THEN
        expect($md5Hash)->toBe(md5($content));
        expect($sha256Hash)->toBe(hash('sha256', $content));
    });

    it('throws IllegalDomainStateException in hash when stream is closed', function (): void {
        // GIVEN
        $stream = FileStream::fromString('test');
        $stream->close();

        // WHEN
        $action = fn (): string => $stream->hash('md5');

        // THEN
        expect($action)->toThrow(IllegalDomainStateException::class);
    });

    it('closes the stream and cleans up on destruct', function (): void {
        // GIVEN
        $resource = fopen('php://temp', 'r+');
        expect($resource)->not->toBeFalse();
        $stream    = new FileStream($resource);
        $resource2 = fopen('php://temp', 'r+');
        expect($resource2)->not->toBeFalse();
        $stream2 = new FileStream($resource2);

        // WHEN
        $stream->close();
        unset($stream2);

        // THEN
        expect(is_resource($resource))->toBeFalse();
        expect(is_resource($resource2))->toBeFalse();
    });
});
