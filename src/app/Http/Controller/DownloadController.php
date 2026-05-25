<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Controller;

use Illuminate\Routing\Controller;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\FileManager;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathNormalizer;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\FilePathProperties;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Service\DiskService;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mime\MimeTypes;

class DownloadController extends Controller
{
    private const OCTET_STREAM_MIME_TYPE = 'application/octet-stream';

    public function __construct(
        private readonly FileManager $fileManager,
        private readonly DiskService $diskService,
        private readonly PathNormalizer $pathNormalizer,
    ) {
    }

    public function __invoke(string $disk, string $path): StreamedResponse
    {
        $filePathProps = $this->resolveFilePathPropsFrom($disk, $path);

        if ($filePathProps->properties->size === 0) {
            return response()->streamDownload(
                function () {
                    echo '';
                },
                $filePathProps->properties->basename,
                [
                    'Content-Type' => self::OCTET_STREAM_MIME_TYPE,
                ]
            );
        }

        $mimeType = $filePathProps->properties->extension ?
            (new MimeTypes())->getMimeTypes($filePathProps->properties->extension)[0] ?? self::OCTET_STREAM_MIME_TYPE :
            self::OCTET_STREAM_MIME_TYPE;

        return response()->streamDownload(
            function () use ($filePathProps) {
                $stream = $this->fileManager->readStream($filePathProps->path);
                echo stream_get_contents($stream->stream());
            },
            $filePathProps->properties->basename,
            ['Content-Type' => $mimeType]
        );
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
