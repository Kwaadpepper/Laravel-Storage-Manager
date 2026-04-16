<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;
use Kwaadpepper\LaravelStorageManager\Http\Dto\BasicOperations\CreatedDirectoryDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\BasicOperations\CreatedFileDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\BasicOperations\DeletedDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\BasicOperations\RenamedDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\ErrorDto;
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

        return Response::json($this->presentCreatedDirectory(), JsonResponse::HTTP_CREATED);
    }

    public function createFile(CreateFileRequest $request): JsonResponse
    {
        $path = Path::appendTo(
            $request->getPath(),
            $request->string('name')->value()
        );
        $content = $request->string('content')->value();

        $this->fileManager->createFile($path, $content);

        return Response::json($this->presentCreatedFile(), JsonResponse::HTTP_CREATED);
    }

    public function delete(DeletePathRequest $request): JsonResponse
    {
        $path = $request->getPath();

        switch (true) {
            case ! $this->fileManager->exists($path):
                return Response::json(
                    $this->presentError('The specified path does not exist.'),
                    JsonResponse::HTTP_NOT_FOUND
                );
            case $this->fileManager->isDirectory($path):
                $this->fileManager->deleteDirectory($path);
                break;
            case $this->fileManager->isFile($path):
                $this->fileManager->deleteFile($path);
                break;
            default:
                return Response::json(
                    $this->presentError('The specified path is invalid.'),
                    JsonResponse::HTTP_BAD_REQUEST
                );
        }

        return Response::json($this->presentDeleted(), JsonResponse::HTTP_NO_CONTENT);
    }

    public function rename(RenamePathRequest $request): JsonResponse
    {
        $path    = $request->getPath();
        $newName = $request->string('to')->value();

        if (! $this->fileManager->exists($path)) {
            return Response::json(
                $this->presentError('The specified path does not exist.'),
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        $this->fileManager->rename($path, $newName);

        return Response::json($this->presentRenamed(), JsonResponse::HTTP_OK);
    }

    private function presentError(string $message): ErrorDto
    {
        return new ErrorDto($message);
    }

    private function presentCreatedDirectory(): CreatedDirectoryDto
    {
        return new CreatedDirectoryDto();
    }

    private function presentCreatedFile(): CreatedFileDto
    {
        return new CreatedFileDto();
    }

    private function presentDeleted(): DeletedDto
    {
        return new DeletedDto();
    }

    private function presentRenamed(): RenamedDto
    {
        return new RenamedDto();
    }
}
