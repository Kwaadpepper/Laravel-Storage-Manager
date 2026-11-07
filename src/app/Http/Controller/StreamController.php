<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Controller;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Http\Response\StreamResponseFactory;
use Kwaadpepper\LaravelStorageManager\Lib\Security\MimeTypeChecker;
use Kwaadpepper\LaravelStorageManager\Lib\StorageGateway;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StreamController extends Controller
{
    public function __construct(
        private readonly StorageGateway $storageGateway,
        private readonly MimeTypeChecker $mimeTypeChecker,
    ) {
    }

    public function __invoke(Request $request, string $disk, string $path): StreamedResponse
    {
        try {
            $resolvedFile = $this->storageGateway->resolveExistingFile($disk, $path);
            $mimeType     = $this->mimeTypeChecker->guessMimeTypeFromExtension($resolvedFile->properties->extension ?? '');
            $stream       = $this->storageGateway->readStream($resolvedFile->disk, $resolvedFile->path, true);

            return StreamResponseFactory::inlineOrRange(
                $stream,
                $resolvedFile->properties->basename,
                $mimeType,
                $resolvedFile->properties->size,
                $request->header('Range'),
            );
        } catch (FileOperationException $e) {
            throw new NotFoundHttpException($e->getMessage(), $e);
        }
    }
}
