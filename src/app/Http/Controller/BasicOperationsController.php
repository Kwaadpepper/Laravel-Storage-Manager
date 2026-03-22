<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;
use Kwaadpepper\LaravelStorageManager\Http\Request\BasicOperations\CreateDirectoryRequest;
use Kwaadpepper\LaravelStorageManager\Http\Request\BasicOperations\DeletePathRequest;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\FileManager;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;

class BasicOperationsController extends Controller
{
    public function __construct(
        private readonly FileManager $fileManager
    ) {
    }

    public function createDirectory(CreateDirectoryRequest $request): JsonResponse
    {
        $path = Path::appendTo(
            $request->getPath(),
            $request->string('name')->value()
        );

        $this->fileManager->createDirectory($path);

        return Response::json([], JsonResponse::HTTP_CREATED);
    }

    public function delete(DeletePathRequest $request): JsonResponse
    {
        $path = $request->getPath();

        if ($this->fileManager->isDirectory($path)) {
            $this->fileManager->deleteDirectory($path);
        } elseif ($this->fileManager->isFile($path)) {
            $this->fileManager->deleteFile($path);
        }

        return Response::json([], JsonResponse::HTTP_NO_CONTENT);
    }
}
