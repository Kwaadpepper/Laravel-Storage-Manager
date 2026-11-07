<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Http\Response\StreamResponseFactory;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\FileStream;

describe('StreamResponseFactory', function (): void {
    it('creates a download StreamedResponse with proper headers', function (): void {
        // GIVEN
        $resource = fopen('php://temp', 'r+');
        fwrite($resource, 'Download file content');
        rewind($resource);
        $stream = new FileStream($resource);

        // WHEN
        $response = StreamResponseFactory::download($stream, 'file.txt', 'text/plain', 21);

        // THEN
        expect($response->getStatusCode())->toBe(200);
        expect($response->headers->get('Content-Type'))->toBe('text/plain');
        expect($response->headers->get('Content-Length'))->toBe('21');
        expect($response->headers->get('Content-Disposition'))->toBe('attachment; filename="file.txt"');

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        expect($output)->toBe('Download file content');
    });

    it('creates an inline StreamedResponse when no range header is given', function (): void {
        // GIVEN
        $resource = fopen('php://temp', 'r+');
        fwrite($resource, 'Inline video content');
        rewind($resource);
        $stream = new FileStream($resource);

        // WHEN
        $response = StreamResponseFactory::inlineOrRange($stream, 'video.mp4', 'video/mp4', 20, null);

        // THEN
        expect($response->getStatusCode())->toBe(200);
        expect($response->headers->get('Content-Type'))->toBe('video/mp4');
        expect($response->headers->get('Content-Length'))->toBe('20');
        expect($response->headers->get('Accept-Ranges'))->toBe('bytes');
        expect($response->headers->get('Content-Disposition'))->toBe('inline; filename="video.mp4"');

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        expect($output)->toBe('Inline video content');
    });

    it('creates a partial content 206 StreamedResponse when valid range header is given', function (): void {
        // GIVEN
        $resource = fopen('php://temp', 'r+');
        fwrite($resource, '0123456789ABCDEF');
        rewind($resource);
        $stream = new FileStream($resource);

        // WHEN
        $response = StreamResponseFactory::inlineOrRange($stream, 'video.mp4', 'video/mp4', 16, 'bytes=4-9');

        // THEN
        expect($response->getStatusCode())->toBe(206);
        expect($response->headers->get('Content-Range'))->toBe('bytes 4-9/16');
        expect($response->headers->get('Content-Length'))->toBe('6');

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        expect($output)->toBe('456789');
    });

    it('creates a 416 Range Not Satisfiable StreamedResponse when invalid range is provided', function (): void {
        // GIVEN
        $resource = fopen('php://temp', 'r+');
        $stream   = new FileStream($resource);

        // WHEN
        $response = StreamResponseFactory::inlineOrRange($stream, 'video.mp4', 'video/mp4', 10, 'bytes=50-60');

        // THEN
        expect($response->getStatusCode())->toBe(416);
        expect($response->headers->get('Content-Range'))->toBe('bytes */10');
    });

    it('returns 304 Not Modified when ETag matches in cachedLocalFile', function (): void {
        // GIVEN
        $tempFile = tempnam(sys_get_temp_dir(), 'cache_test_');
        expect($tempFile)->not->toBeFalse();
        file_put_contents((string) $tempFile, 'thumbnail content');

        try {
            $etag = '"' . basename((string) $tempFile) . '"';

            // WHEN
            $response = StreamResponseFactory::cachedLocalFile((string) $tempFile, 'image/webp', 604800, $etag);

            // THEN
            expect($response->getStatusCode())->toBe(304);
            expect($response->headers->get('ETag'))->toBe($etag);
        } finally {
            if (file_exists((string) $tempFile)) {
                unlink((string) $tempFile);
            }
        }
    });

    it('returns 200 StreamedResponse when ETag does not match in cachedLocalFile', function (): void {
        // GIVEN
        $tempFile = tempnam(sys_get_temp_dir(), 'cache_test_');
        expect($tempFile)->not->toBeFalse();
        file_put_contents((string) $tempFile, 'thumbnail content');

        try {
            // WHEN
            $response = StreamResponseFactory::cachedLocalFile((string) $tempFile, 'image/webp', 604800, null);

            // THEN
            expect($response->getStatusCode())->toBe(200);
            expect($response->headers->get('Content-Type'))->toBe('image/webp');
            expect($response->headers->get('Content-Length'))->toBe('17');
            expect($response->headers->get('Cache-Control'))->toContain('public');
            expect($response->headers->get('Cache-Control'))->toContain('max-age=604800');

            ob_start();
            $response->sendContent();
            $output = ob_get_clean();

            expect($output)->toBe('thumbnail content');
        } finally {
            if (file_exists((string) $tempFile)) {
                unlink((string) $tempFile);
            }
        }
    });

    it('creates an SSE stream response and formats messages properly', function (): void {
        // GIVEN

        // WHEN
        $response = StreamResponseFactory::sse(static function (callable $emit): void {
            $emit(['status' => 'uploading', 'progress' => 50]);
            $emit(['status' => 'completed', 'progress' => 100]);
        });

        // THEN
        expect($response->getStatusCode())->toBe(200);
        expect($response->headers->get('Content-Type'))->toBe('text/event-stream');
        expect($response->headers->get('Cache-Control'))->toContain('no-cache');

        ob_start();
        ob_start();
        $response->sendContent();
        ob_end_clean();
        $output = (string) ob_get_clean();

        expect($output)->toContain('data: {"status":"uploading","progress":50}');
        expect($output)->toContain('data: {"status":"completed","progress":100}');
    });
});
