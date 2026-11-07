<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Controller;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Routing\Controller;
use Kwaadpepper\LaravelStorageManager\Acl\SmAbility;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Http\Response\StreamResponseFactory;
use Kwaadpepper\LaravelStorageManager\Lib\Security\MimeTypeChecker;
use Kwaadpepper\LaravelStorageManager\Lib\StorageGateway;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Service\AuthService;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DownloadController extends Controller
{
    public function __construct(
        private readonly StorageGateway $storageGateway,
        private readonly MimeTypeChecker $mimeTypeChecker,
        private readonly Gate $gate,
        private readonly AuthService $authService,
    ) {
    }

    public function __invoke(Disk $disk, string $path): StreamedResponse
    {
        $targetPath = new Path('/' . ltrim($path, '/'));
        $user       = $this->authService->user();
        $this->gate->forUser($user)->authorize(SmAbility::VIEW, [$disk, $targetPath]);

        try {
            $resolvedFile = $this->storageGateway->resolveExistingFile($disk, $path);
            $mimeType     = $this->mimeTypeChecker->guessMimeTypeFromExtension($resolvedFile->properties->extension ?? '');
            $stream       = $this->storageGateway->readStream($resolvedFile->disk, $resolvedFile->path, true);

            return StreamResponseFactory::download(
                $stream,
                $resolvedFile->properties->basename,
                $mimeType,
                $resolvedFile->properties->size,
            );
        } catch (FileOperationException $e) {
            throw new NotFoundHttpException($e->getMessage(), $e);
        }
    }
}
