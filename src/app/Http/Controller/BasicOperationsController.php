<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;
use Kwaadpepper\LaravelStorageManager\Http\Request\BasicOperations\CreateDirectoryRequest;
use Kwaadpepper\LaravelStorageManager\Http\Request\BasicOperations\CreateFileRequest;
use Kwaadpepper\LaravelStorageManager\Http\Request\BasicOperations\DeletePathRequest;
use Kwaadpepper\LaravelStorageManager\Http\Request\BasicOperations\RenamePathRequest;
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

    public function createFile(CreateFileRequest $request): JsonResponse
    {
        $path = Path::appendTo(
            $request->getPath(),
            $request->string('name')->value()
        );
        $content = $request->string('content')->value();

        $this->fileManager->createFile($path, $content);

        return Response::json([], JsonResponse::HTTP_CREATED);
    }

    public function delete(DeletePathRequest $request): JsonResponse
    {
        $path = $request->getPath();

        switch (true) {
            case ! $this->fileManager->exists($path):
                return Response::json([
                    'error' => 'The specified path does not exist.',
                ], JsonResponse::HTTP_NOT_FOUND);
            case $this->fileManager->isDirectory($path):
                $this->fileManager->deleteDirectory($path);
                break;
            case $this->fileManager->isFile($path):
                $this->fileManager->deleteFile($path);
                break;
            default:
                return Response::json([
                    'error' => 'The specified path is invalid.',
                ], JsonResponse::HTTP_BAD_REQUEST);
        }

        return Response::json([], JsonResponse::HTTP_NO_CONTENT);
    }

    public function rename(RenamePathRequest $request): JsonResponse
    {
        $path    = $request->getPath();
        $newName = $request->string('to')->value();

        if (! $this->fileManager->exists($path)) {
            return Response::json([
                'error' => 'The specified path does not exist.',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $this->fileManager->rename($path, $newName);

        return Response::json([], JsonResponse::HTTP_OK);
    }
}
