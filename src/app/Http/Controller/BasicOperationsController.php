<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Controller;

use Illuminate\Routing\Controller;
use Kwaadpepper\LaravelStorageManager\Action\Archive\UnzipAction;
use Kwaadpepper\LaravelStorageManager\Action\Archive\UnzipInput;
use Kwaadpepper\LaravelStorageManager\Action\Archive\ZipAction;
use Kwaadpepper\LaravelStorageManager\Action\Archive\ZipInput;
use Kwaadpepper\LaravelStorageManager\Action\File\BulkRenameAction;
use Kwaadpepper\LaravelStorageManager\Action\File\BulkRenameInput;
use Kwaadpepper\LaravelStorageManager\Action\File\CopyPathAction;
use Kwaadpepper\LaravelStorageManager\Action\File\CopyPathInput;
use Kwaadpepper\LaravelStorageManager\Action\File\CreateDirectoryAction;
use Kwaadpepper\LaravelStorageManager\Action\File\CreateDirectoryInput;
use Kwaadpepper\LaravelStorageManager\Action\File\CreateFileAction;
use Kwaadpepper\LaravelStorageManager\Action\File\CreateFileInput;
use Kwaadpepper\LaravelStorageManager\Action\File\DeletePathAction;
use Kwaadpepper\LaravelStorageManager\Action\File\DeletePathInput;
use Kwaadpepper\LaravelStorageManager\Action\File\MovePathAction;
use Kwaadpepper\LaravelStorageManager\Action\File\MovePathInput;
use Kwaadpepper\LaravelStorageManager\Action\File\RenamePathAction;
use Kwaadpepper\LaravelStorageManager\Action\File\RenamePathInput;
use Kwaadpepper\LaravelStorageManager\Contract\Executor;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Http\Dto\BasicOperations\BulkRenamedDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\BasicOperations\CopiedDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\BasicOperations\CreatedDirectoryDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\BasicOperations\CreatedFileDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\BasicOperations\DeletedDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\BasicOperations\ExistsDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\BasicOperations\MovedDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\BasicOperations\PropertiesDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\BasicOperations\RenamedDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\BasicOperations\UnzippedDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\BasicOperations\ZippedDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\ErrorDto;
use Kwaadpepper\LaravelStorageManager\Http\Request\BasicOperations\BulkRenameRequest;
use Kwaadpepper\LaravelStorageManager\Http\Request\BasicOperations\CopyPathRequest;
use Kwaadpepper\LaravelStorageManager\Http\Request\BasicOperations\CreateDirectoryRequest;
use Kwaadpepper\LaravelStorageManager\Http\Request\BasicOperations\CreateFileRequest;
use Kwaadpepper\LaravelStorageManager\Http\Request\BasicOperations\DeletePathRequest;
use Kwaadpepper\LaravelStorageManager\Http\Request\BasicOperations\MovePathRequest;
use Kwaadpepper\LaravelStorageManager\Http\Request\BasicOperations\PropertiesPathRequest;
use Kwaadpepper\LaravelStorageManager\Http\Request\BasicOperations\RenamePathRequest;
use Kwaadpepper\LaravelStorageManager\Http\Request\BasicOperations\UnzipRequest;
use Kwaadpepper\LaravelStorageManager\Http\Request\BasicOperations\ZipRequest;
use Kwaadpepper\LaravelStorageManager\Http\Response\ApiResponse;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Query\File\CheckPathExistsInput;
use Kwaadpepper\LaravelStorageManager\Query\File\CheckPathExistsQuery;
use Kwaadpepper\LaravelStorageManager\Query\File\GetPathPropertiesInput;
use Kwaadpepper\LaravelStorageManager\Query\File\GetPathPropertiesQuery;

class BasicOperationsController extends Controller
{
    private const string ERROR_PATH_NOT_FOUND = 'The specified path does not exist.';

    public function __construct(
        private readonly Executor $executor,
        private readonly GetPathPropertiesQuery $propertiesQuery,
        private readonly CheckPathExistsQuery $existsQuery,
        private readonly CreateDirectoryAction $createDirectoryAction,
        private readonly CreateFileAction $createFileAction,
        private readonly DeletePathAction $deleteAction,
        private readonly RenamePathAction $renameAction,
        private readonly CopyPathAction $copyAction,
        private readonly MovePathAction $moveAction,
        private readonly BulkRenameAction $bulkRenameAction,
        private readonly ZipAction $zipAction,
        private readonly UnzipAction $unzipAction,
    ) {
    }

    public function properties(PropertiesPathRequest $request): ApiResponse
    {
        return $this->handleNotFound(function () use ($request): ApiResponse {
            $fileProperties = $this->executor->execute(
                $this->propertiesQuery,
                new GetPathPropertiesInput($request->getDisk(), $request->getPath())
            );

            return ApiResponse::json(PropertiesDto::fromPathProperties($fileProperties), ApiResponse::HTTP_OK);
        });
    }

    public function exists(PropertiesPathRequest $request): ApiResponse
    {
        $exists = $this->executor->execute(
            $this->existsQuery,
            new CheckPathExistsInput($request->getDisk(), $request->getPath())
        );

        return ApiResponse::json(
            new ExistsDto($exists),
            ApiResponse::HTTP_OK
        );
    }

    public function createDirectory(CreateDirectoryRequest $request): ApiResponse
    {
        $path = Path::appendTo(
            $request->getPath(),
            $request->string('name')->value()
        );

        $this->executor->execute(
            $this->createDirectoryAction,
            new CreateDirectoryInput($request->getDisk(), $path)
        );

        return ApiResponse::json(new CreatedDirectoryDto(), ApiResponse::HTTP_CREATED);
    }

    public function createFile(CreateFileRequest $request): ApiResponse
    {
        $path = Path::appendTo(
            $request->getPath(),
            $request->string('name')->value()
        );

        $this->executor->execute(
            $this->createFileAction,
            new CreateFileInput(
                $request->getDisk(),
                $path,
                $request->string('content')->value()
            )
        );

        return ApiResponse::json(new CreatedFileDto(), ApiResponse::HTTP_CREATED);
    }

    public function delete(DeletePathRequest $request): ApiResponse
    {
        return $this->handleNotFound(function () use ($request): ApiResponse {
            $trashedItem = $this->executor->execute(
                $this->deleteAction,
                new DeletePathInput(
                    $request->getDisk(),
                    $request->getPath(),
                    $request->boolean('force')
                )
            );

            if ($trashedItem !== null) {
                return ApiResponse::json(
                    new DeletedDto(trashed: true, trashId: $trashedItem->trashId),
                    ApiResponse::HTTP_OK
                );
            }

            return ApiResponse::json(new DeletedDto(), ApiResponse::HTTP_NO_CONTENT);
        });
    }

    public function rename(RenamePathRequest $request): ApiResponse
    {
        return $this->handleNotFound(function () use ($request): ApiResponse {
            $this->executor->execute(
                $this->renameAction,
                new RenamePathInput(
                    $request->getDisk(),
                    $request->getPath(),
                    $request->string('to')->value()
                )
            );

            return ApiResponse::json(new RenamedDto(), ApiResponse::HTTP_OK);
        });
    }

    public function copy(CopyPathRequest $request): ApiResponse
    {
        return $this->handleNotFound(function () use ($request): ApiResponse {
            $this->executor->execute(
                $this->copyAction,
                new CopyPathInput(
                    $request->getDisk(),
                    $request->getPath(),
                    $request->getDestinationDir()
                )
            );

            return ApiResponse::json(new CopiedDto(), ApiResponse::HTTP_OK);
        });
    }

    public function move(MovePathRequest $request): ApiResponse
    {
        return $this->handleNotFound(function () use ($request): ApiResponse {
            $this->executor->execute(
                $this->moveAction,
                new MovePathInput(
                    $request->getDisk(),
                    $request->getPath(),
                    $request->getDestinationDir()
                )
            );

            return ApiResponse::json(new MovedDto(), ApiResponse::HTTP_OK);
        });
    }

    public function bulkRename(BulkRenameRequest $request): ApiResponse
    {
        $results = $this->executor->execute(
            $this->bulkRenameAction,
            new BulkRenameInput($request->getDisk(), $request->getRenames())
        );

        return ApiResponse::json(new BulkRenamedDto($results), ApiResponse::HTTP_OK);
    }

    public function zip(ZipRequest $request): ApiResponse
    {
        $archivePath = $this->executor->execute(
            $this->zipAction,
            new ZipInput(
                $request->getDisk(),
                $request->getPaths(),
                $request->getDestination()
            )
        );

        return ApiResponse::json(new ZippedDto($archivePath->value), ApiResponse::HTTP_CREATED);
    }

    public function unzip(UnzipRequest $request): ApiResponse
    {
        $destinationDir = $request->getDestinationDir();

        $this->executor->execute(
            $this->unzipAction,
            new UnzipInput(
                $request->getDisk(),
                $request->getPath(),
                $destinationDir
            )
        );

        return ApiResponse::json(new UnzippedDto($destinationDir->value), ApiResponse::HTTP_OK);
    }

    /**
     * @param  callable(): ApiResponse  $callback
     */
    private function handleNotFound(callable $callback): ApiResponse
    {
        try {
            return $callback();
        } catch (FileOperationException $e) {
            if ($e->getDomainErrorCode() === FileOperationError::FILE_NOT_FOUND) {
                return ApiResponse::json(
                    new ErrorDto(self::ERROR_PATH_NOT_FOUND),
                    ApiResponse::HTTP_NOT_FOUND
                );
            }

            throw $e;
        }
    }
}
