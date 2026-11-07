<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Controller;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Http\Response\StreamResponseFactory;
use Kwaadpepper\LaravelStorageManager\Lib\StorageGateway;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Service\ThumbnailService;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ThumbnailController extends Controller
{
    public function __construct(
        private readonly StorageGateway $storageGateway,
        private readonly StorageManagerConfig $config,
        private readonly ThumbnailService $thumbnailService,
    ) {
    }

    public function __invoke(Request $request, Disk $disk, string $path): StreamedResponse
    {
        if (! $this->config->thumbnails->thumbsAreEnabled) {
            throw new NotFoundHttpException();
        }

        try {
            $resolvedFile = $this->storageGateway->resolveExistingFile($disk, $path);

            $cachedFilePath = $this->thumbnailService->getOrCreateThumbnail(
                $resolvedFile->disk,
                $resolvedFile->path,
                $resolvedFile->properties
            );

            if ($cachedFilePath === null || ! file_exists($cachedFilePath)) {
                throw new NotFoundHttpException();
            }

            $extension = strtolower(pathinfo($cachedFilePath, PATHINFO_EXTENSION));
            $mimeType  = $extension === 'svg' ? 'image/svg+xml' : 'image/webp';

            return StreamResponseFactory::cachedLocalFile(
                $cachedFilePath,
                $mimeType,
                $this->config->thumbnails->cacheDuration,
                $request->headers->get('If-None-Match'),
            );
        } catch (FileOperationException $e) {
            throw new NotFoundHttpException($e->getMessage(), $e);
        }
    }
}
