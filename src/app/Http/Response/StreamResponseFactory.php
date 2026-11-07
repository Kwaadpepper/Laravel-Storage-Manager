<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Response;

use Illuminate\Support\Facades\Response as ResponseFacade;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\FileStream;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class StreamResponseFactory
{
    public static function download(FileStream $stream, string $fileName, string $mimeType, int $size): StreamedResponse
    {
        return ResponseFacade::stream(
            static function () use ($stream): void {
                self::passthruStream($stream);
            },
            Response::HTTP_OK,
            [
                'Content-Type'        => $mimeType,
                'Content-Length'      => (string) $size,
                'Content-Disposition' => sprintf('attachment; filename="%s"', addcslashes($fileName, '"')),
            ]
        );
    }

    public static function inlineOrRange(
        FileStream $stream,
        string $fileName,
        string $mimeType,
        int $size,
        ?string $rangeHeader,
    ): StreamedResponse {
        $disposition = sprintf('inline; filename="%s"', addcslashes($fileName, '"'));

        if ($size === 0) {
            return ResponseFacade::stream(
                static function () use ($stream): void {
                    self::passthruStream($stream);
                },
                Response::HTTP_OK,
                [
                    'Content-Type'        => $mimeType,
                    'Content-Length'      => '0',
                    'Accept-Ranges'       => 'bytes',
                    'Content-Disposition' => $disposition,
                ]
            );
        }

        $range = self::parseRangeHeader($rangeHeader, $size);

        if ($range === false) {
            $stream->close();

            return ResponseFacade::stream(
                static function (): void {
                },
                Response::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE,
                ['Content-Range' => sprintf('bytes */%d', $size)]
            );
        }

        if ($range === null) {
            return ResponseFacade::stream(
                static function () use ($stream): void {
                    self::passthruStream($stream);
                },
                Response::HTTP_OK,
                [
                    'Content-Type'        => $mimeType,
                    'Content-Length'      => (string) $size,
                    'Accept-Ranges'       => 'bytes',
                    'Content-Disposition' => $disposition,
                ]
            );
        }

        $start  = $range['start'];
        $length = $range['length'];
        $end    = $start + $length - 1;

        return ResponseFacade::stream(
            static function () use ($stream, $start, $length): void {
                self::passthruRangeStream($stream, $start, $length);
            },
            Response::HTTP_PARTIAL_CONTENT,
            [
                'Content-Type'        => $mimeType,
                'Content-Length'      => (string) $length,
                'Content-Range'       => sprintf('bytes %d-%d/%d', $start, $end, $size),
                'Accept-Ranges'       => 'bytes',
                'Content-Disposition' => $disposition,
            ]
        );
    }

    public static function cachedLocalFile(
        string $localPath,
        string $mimeType,
        int $cacheDuration,
        ?string $ifNoneMatch,
    ): StreamedResponse {
        $etag         = '"' . basename($localPath) . '"';
        $cacheControl = sprintf('public, max-age=%d, immutable', $cacheDuration);

        if ($ifNoneMatch === $etag) {
            return ResponseFacade::stream(
                static function (): void {
                },
                Response::HTTP_NOT_MODIFIED,
                [
                    'ETag'          => $etag,
                    'Cache-Control' => $cacheControl,
                ]
            );
        }

        $fileSize = (int) filesize($localPath);

        return ResponseFacade::stream(
            static function () use ($localPath): void {
                self::passthruStream(FileStream::fromPath($localPath));
            },
            Response::HTTP_OK,
            [
                'Content-Type'        => $mimeType,
                'Content-Length'      => (string) $fileSize,
                'ETag'                => $etag,
                'Cache-Control'       => $cacheControl,
                'Content-Disposition' => sprintf('inline; filename="thumb-%s"', basename($localPath)),
            ]
        );
    }

    /**
     * @param  callable(callable(array<string, mixed>): void): void  $callback
     */
    public static function sse(callable $callback): StreamedResponse
    {
        set_time_limit(0);
        ignore_user_abort(true);

        return ResponseFacade::stream(
            static function () use ($callback): void {
                $emit = static function (array $payload): void {
                    echo 'data: ' . json_encode($payload) . "\n\n";
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                };

                $callback($emit);
            },
            Response::HTTP_OK,
            [
                'Content-Type'      => 'text/event-stream',
                'Cache-Control'     => 'no-cache',
                'X-Accel-Buffering' => 'no',
                'Connection'        => 'keep-alive',
            ]
        );
    }

    /**
     * @return array{start: int, end: int, length: int}|null|false Null when no range header, false when invalid range.
     */
    private static function parseRangeHeader(?string $rangeHeader, int $fileSize): array | null | false
    {
        if ($rangeHeader === null || ! preg_match('/^bytes=(\d*)-(\d*)$/i', trim($rangeHeader), $matches)) {
            return null;
        }

        $startStr = $matches[1];
        $endStr   = $matches[2];

        if ($startStr !== '' && $endStr !== '') {
            $start = (int) $startStr;
            $end   = (int) $endStr;
        } elseif ($startStr !== '') {
            $start = (int) $startStr;
            $end   = $fileSize - 1;
        } elseif ($endStr !== '') {
            $start = max(0, $fileSize - (int) $endStr);
            $end   = $fileSize - 1;
        } else {
            return false;
        }

        if ($start > $end || $start >= $fileSize) {
            return false;
        }

        $end    = min($end, $fileSize - 1);
        $length = $end - $start + 1;

        return ['start' => $start, 'end' => $end, 'length' => $length];
    }

    private static function passthruStream(FileStream $stream): void
    {
        try {
            fpassthru($stream->stream());
        } finally {
            $stream->close();
        }
    }

    private static function passthruRangeStream(
        FileStream $stream,
        int $offset,
        int $length,
        int $chunkSize = 65536,
    ): void {
        try {
            $stream->seek($offset);
            $remaining = $length;

            while ($remaining > 0 && ! $stream->eof()) {
                $chunk = $stream->read(min($chunkSize, $remaining));
                echo $chunk;
                flush();
                if ($chunk === '') {
                    break;
                }
                $remaining -= strlen($chunk);
            }
        } finally {
            $stream->close();
        }
    }
}
