<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Controller;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\FileManager;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathNormalizer;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\FilePathProperties;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Repository\ConfigRepository;
use Kwaadpepper\LaravelStorageManager\Service\DiskService;
use Kwaadpepper\LaravelStorageManager\Service\ThumbnailService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ThumbnailController extends Controller
{
    public function __construct(
        private readonly FileManager $fileManager,
        private readonly DiskService $diskService,
        private readonly PathNormalizer $pathNormalizer,
        private readonly ConfigRepository $configRepository,
        private readonly ThumbnailService $thumbnailService,
    ) {
    }

    public function __invoke(Request $request, string $disk, string $path): StreamedResponse
    {
        if (! $this->configRepository->isThumbnailsEnabled()) {
            throw new NotFoundHttpException();
        }

        $filePathProps = $this->resolveFilePathPropsFrom($disk, $path);
        $diskObject    = $this->diskService->getDisk($disk);

        $cachedFilePath = $this->thumbnailService->getOrCreateThumbnail(
            $diskObject,
            $filePathProps->path,
            $filePathProps->properties
        );

        if ($cachedFilePath === null || ! file_exists($cachedFilePath)) {
            throw new NotFoundHttpException();
        }

        $etag = sprintf('"%s"', sha1_file($cachedFilePath) ?: '');
        if ($request->headers->get('If-None-Match') === $etag) {
            return response()->stream(
                function (): void {
                },
                Response::HTTP_NOT_MODIFIED,
                [
                    'ETag'          => $etag,
                    'Cache-Control' => 'public, max-age=604800, immutable',
                ]
            );
        }

        $extension = strtolower(pathinfo($cachedFilePath, PATHINFO_EXTENSION));
        $mimeType  = $extension === 'svg' ? 'image/svg+xml' : 'image/webp';
        $fileSize  = filesize($cachedFilePath);

        return response()->stream(
            function () use ($cachedFilePath): void {
                $handle = fopen($cachedFilePath, 'rb');
                if (is_resource($handle)) {
                    fpassthru($handle);
                    fclose($handle);
                }
            },
            Response::HTTP_OK,
            [
                'Content-Type'        => $mimeType,
                'Content-Length'      => (string) $fileSize,
                'ETag'                => $etag,
                'Cache-Control'       => 'public, max-age=604800, immutable',
                'Content-Disposition' => sprintf('inline; filename="thumb-%s"', basename($cachedFilePath)),
            ]
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
