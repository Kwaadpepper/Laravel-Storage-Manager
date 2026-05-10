<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Controller;

use Illuminate\Routing\Controller;
use Kwaadpepper\LaravelStorageManager\Http\Dto\BasicOperations\CreatedDirectoryDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\BasicOperations\CreatedFileDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\BasicOperations\DeletedDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\BasicOperations\PropertiesDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\BasicOperations\RenamedDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\ErrorDto;
use Kwaadpepper\LaravelStorageManager\Http\Request\BasicOperations\CreateDirectoryRequest;
use Kwaadpepper\LaravelStorageManager\Http\Request\BasicOperations\CreateFileRequest;
use Kwaadpepper\LaravelStorageManager\Http\Request\BasicOperations\DeletePathRequest;
use Kwaadpepper\LaravelStorageManager\Http\Request\BasicOperations\PropertiesPathRequest;
use Kwaadpepper\LaravelStorageManager\Http\Request\BasicOperations\RenamePathRequest;
use Kwaadpepper\LaravelStorageManager\Http\Response\ApiResponse;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\FileManager;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\FilePathProperties;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathProperties;

class BasicOperationsController extends Controller
{
    private const ERROR_PATH_NOT_FOUND = 'The specified path does not exist.';

    public function __construct(
        private readonly FileManager $fileManager
    ) {
    }

    public function properties(PropertiesPathRequest $request): ApiResponse
    {
        $path = $request->getPath();

        if (! $this->fileManager->exists($path)) {
            return ApiResponse::json(
                $this->presentError(self::ERROR_PATH_NOT_FOUND),
                ApiResponse::HTTP_NOT_FOUND
            );
        }

        $fileProperties = $this->fileManager->getProperties($path);

        return ApiResponse::json($this->presentProperties($fileProperties), ApiResponse::HTTP_OK);
    }

    public function createDirectory(CreateDirectoryRequest $request): ApiResponse
    {
        $path = Path::appendTo(
            $request->getPath(),
            $request->string('name')->value()
        );

        $this->fileManager->createDirectory($path);

        return ApiResponse::json($this->presentCreatedDirectory(), ApiResponse::HTTP_CREATED);
    }

    public function createFile(CreateFileRequest $request): ApiResponse
    {
        $path = Path::appendTo(
            $request->getPath(),
            $request->string('name')->value()
        );
        $content = $request->string('content')->value();

        $this->fileManager->createFile($path, $content);

        return ApiResponse::json($this->presentCreatedFile(), ApiResponse::HTTP_CREATED);
    }

    public function delete(DeletePathRequest $request): ApiResponse
    {
        $path = $request->getPath();

        switch (true) {
            case ! $this->fileManager->exists($path):
                return ApiResponse::json(
                    $this->presentError(self::ERROR_PATH_NOT_FOUND),
                    ApiResponse::HTTP_NOT_FOUND
                );
            case $this->fileManager->isDirectory($path):
                $this->fileManager->deleteDirectory($path);
                break;
            case $this->fileManager->isFile($path):
                $this->fileManager->deleteFile($path);
                break;
            default:
                return ApiResponse::json(
                    $this->presentError('The specified path is invalid.'),
                    ApiResponse::HTTP_BAD_REQUEST
                );
        }

        return ApiResponse::json($this->presentDeleted(), ApiResponse::HTTP_NO_CONTENT);
    }

    public function rename(RenamePathRequest $request): ApiResponse
    {
        $path    = $request->getPath();
        $newName = $request->string('to')->value();

        if (! $this->fileManager->exists($path)) {
            return ApiResponse::json(
                $this->presentError(self::ERROR_PATH_NOT_FOUND),
                ApiResponse::HTTP_NOT_FOUND
            );
        }

        $this->fileManager->rename($path, $newName);

        return ApiResponse::json($this->presentRenamed(), ApiResponse::HTTP_OK);
    }

    private function presentError(string $message): ErrorDto
    {
        return new ErrorDto($message);
    }

    private function presentProperties(PathProperties $properties): PropertiesDto
    {
        return new PropertiesDto(
            path: (string) $properties->path,
            basename: $properties->basename,
            dirname: $properties->dirname,
            timestamp: $properties->timestamp,
            visibility: $properties->visibility->value,
            isFile: $properties->isFile,
            isDirectory: $properties->isDirectory,
            extension: $properties instanceof FilePathProperties ? $properties->extension : null,
            size: $properties instanceof FilePathProperties ? $properties->size : null,
        );
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
