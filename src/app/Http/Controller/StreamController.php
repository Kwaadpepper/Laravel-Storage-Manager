<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Controller;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\FileManager;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathNormalizer;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\FilePathProperties;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Service\DiskService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mime\MimeTypes;

class StreamController extends Controller
{
    private const string OCTET_STREAM_MIME_TYPE = 'application/octet-stream';
    private const int CHUNK_SIZE = 65536; // 64 KB

    private const array MIME_OVERRIDES = [
        'mp4'  => 'video/mp4',
        'm4v'  => 'video/mp4',
        'mov'  => 'video/quicktime',
        'webm' => 'video/webm',
        'ogg'  => 'video/ogg',
        'ogv'  => 'video/ogg',
        'mkv'  => 'video/x-matroska',
        'mp3'  => 'audio/mpeg',
        'wav'  => 'audio/wav',
        'flac' => 'audio/flac',
        'aac'  => 'audio/aac',
        'm4a'  => 'audio/mp4',
        'opus' => 'audio/opus',
    ];

    public function __construct(
        private readonly FileManager $fileManager,
        private readonly DiskService $diskService,
        private readonly PathNormalizer $pathNormalizer,
    ) {
    }

    public function __invoke(Request $request, string $disk, string $path): StreamedResponse
    {
        $filePathProps = $this->resolveFilePathPropsFrom($disk, $path);
        $fileSize      = $filePathProps->properties->size;
        $mimeType      = $this->resolveMimeType($filePathProps->properties->extension);
        $disposition   = sprintf('inline; filename="%s"', addcslashes($filePathProps->properties->basename, '"'));

        if ($fileSize === 0) {
            return $this->streamEmpty($mimeType, $disposition);
        }

        $range = $this->parseRangeHeader($request->header('Range'), $fileSize);

        if ($range === false) {
            return response()->stream(
                function (): void {
                },
                Response::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE,
                ['Content-Range' => sprintf('bytes */%d', $fileSize)]
            );
        }

        if ($range === null) {
            return $this->streamFull($filePathProps->path, $mimeType, $fileSize, $disposition);
        }

        return $this->streamRange(
            $filePathProps->path,
            $mimeType,
            $fileSize,
            $disposition,
            $range['start'],
            $range['length']
        );
    }

    private function resolveMimeType(?string $extension): string
    {
        if ($extension === null || $extension === '') {
            return self::OCTET_STREAM_MIME_TYPE;
        }

        $ext = strtolower($extension);
        if (isset(self::MIME_OVERRIDES[$ext])) {
            return self::MIME_OVERRIDES[$ext];
        }

        return (new MimeTypes())->getMimeTypes($ext)[0] ?? self::OCTET_STREAM_MIME_TYPE;
    }

    private function streamEmpty(string $mimeType, string $disposition): StreamedResponse
    {
        return response()->stream(
            function (): void {
                echo '';
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

    private function streamFull(Path $path, string $mimeType, int $fileSize, string $disposition): StreamedResponse
    {
        return response()->stream(
            function () use ($path): void {
                $streamResource = $this->fileManager->readStream($path)->stream();
                if (is_resource($streamResource)) {
                    fpassthru($streamResource);
                    fclose($streamResource);
                }
            },
            Response::HTTP_OK,
            [
                'Content-Type'        => $mimeType,
                'Content-Length'      => (string) $fileSize,
                'Accept-Ranges'       => 'bytes',
                'Content-Disposition' => $disposition,
            ]
        );
    }

    /**
     * @return array{start: int, end: int, length: int}|null|false Null when no range header, false when invalid range.
     */
    private function parseRangeHeader(mixed $rangeHeader, int $fileSize): array|null|false
    {
        if (! is_string($rangeHeader) || ! preg_match('/^bytes=(\d*)-(\d*)$/i', mb_trim($rangeHeader), $matches)) {
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

    private function streamRange(
        Path $path,
        string $mimeType,
        int $fileSize,
        string $disposition,
        int $start,
        int $length
    ): StreamedResponse {
        $end = $start + $length - 1;

        return response()->stream(
            function () use ($path, $start, $length): void {
                $streamResource = $this->fileManager->readStream($path)->stream();
                if (! is_resource($streamResource)) {
                    return;
                }

                $this->seekStream($streamResource, $start);
                $this->sendChunks($streamResource, $length);

                if (is_resource($streamResource)) {
                    fclose($streamResource);
                }
            },
            Response::HTTP_PARTIAL_CONTENT,
            [
                'Content-Type'        => $mimeType,
                'Content-Length'      => (string) $length,
                'Content-Range'       => sprintf('bytes %d-%d/%d', $start, $end, $fileSize),
                'Accept-Ranges'       => 'bytes',
                'Content-Disposition' => $disposition,
            ]
        );
    }

    /**
     * @param  resource  $resource
     */
    private function seekStream(mixed $resource, int $start): void
    {
        if ($start <= 0 || ! is_resource($resource)) {
            return;
        }

        $meta = stream_get_meta_data($resource);
        if ($meta['seekable']) {
            fseek($resource, $start);

            return;
        }

        $toSkip = $start;
        while ($toSkip > 0 && ! feof($resource)) {
            $chunk = fread($resource, min(8192, $toSkip));
            if ($chunk === false || $chunk === '') {
                break;
            }
            $toSkip -= strlen($chunk);
        }
    }

    /**
     * @param  resource  $resource
     */
    private function sendChunks(mixed $resource, int $length): void
    {
        $remaining = $length;
        while ($remaining > 0 && is_resource($resource) && ! feof($resource)) {
            $chunk = fread($resource, min(self::CHUNK_SIZE, $remaining));
            if ($chunk === false || $chunk === '') {
                break;
            }
            echo $chunk;
            flush();
            $remaining -= strlen($chunk);
        }
    }

    /**
     * @return object{path:Path,properties:FilePathProperties}
     */
    private function resolveFilePathPropsFrom(string $disk, string $path): object
    {
        $disks = $this->diskService->getDiskNamesList();

        if (! in_array($disk, $disks, true)) {
            throw new NotFoundHttpException();
        }

        $diskObject     = $this->diskService->getDisk($disk);
        $normalizedPath = $this->pathNormalizer->normalizePath(rawurldecode($path));
        $pathObject     = new Path($normalizedPath);

        $this->fileManager->setActiveDisk($diskObject);
        if (! $this->fileManager->exists($pathObject)) {
            throw new NotFoundHttpException();
        }

        $properties = $this->fileManager->getProperties($pathObject);

        if (! ($properties instanceof FilePathProperties)) {
            throw new NotFoundHttpException();
        }

        return (object) [
            'path'       => $pathObject,
            'properties' => $properties,
        ];
    }
}
