<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Exception\IllegalDomainStateException;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\FileStream;

describe('FileStream Value Object', function (): void {
    it('creates a FileStream with a valid resource', function (): void {
        // Given
        $resource = fopen('php://temp', 'r+');

        // When
        $fileStream = new FileStream($resource);

        // Then
        expect($fileStream->stream())->toBe($resource);
    });

    it('throws exception if not a valid resource', function (): void {
        // Given
        $invalidResource = 'not-a-resource';

        // When & Then
        expect(fn () => new FileStream($invalidResource))
            ->toThrow(IllegalDomainStateException::class, 'The provided value is not a valid stream resource.');
    });

    it('throws exception when accessing a closed stream', function (): void {
        // Given
        $resource   = fopen('php://temp', 'r+');
        $fileStream = new FileStream($resource);

        // When
        fclose($resource);

        // Then
        expect(fn () => $fileStream->stream())
            ->toThrow(IllegalDomainStateException::class, 'The stream resource is no longer valid.');
    });

    it('returns the correct size of the stream', function (): void {
        // Given
        $resource = fopen('php://temp', 'r+');
        fwrite($resource, 'Hello World!');
        $fileStream = new FileStream($resource);

        // When
        $size = $fileStream->size();

        // Then
        expect($size)->toBe(12);
    });

    it('returns zero size when accessing a closed stream size', function (): void {
        // Given
        $resource   = fopen('php://temp', 'r+');
        $fileStream = new FileStream($resource);

        // When
        fclose($resource);
        $size = $fileStream->size();

        // Then
        expect($size)->toBe(0);
    });
});
