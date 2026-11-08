<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Action\File;

use Kwaadpepper\LaravelStorageManager\Acl\SmAbility;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Contract\Action;
use Kwaadpepper\LaravelStorageManager\Contract\ActionInput;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;
use Kwaadpepper\LaravelStorageManager\Event\DirectoryDeleted;
use Kwaadpepper\LaravelStorageManager\Event\DirectoryDeleting;
use Kwaadpepper\LaravelStorageManager\Event\FileDeleted;
use Kwaadpepper\LaravelStorageManager\Event\FileDeleting;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Lib\Event\EventDispatcher;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathNormalizer;
use Kwaadpepper\LaravelStorageManager\Lib\StorageGateway;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Trash\TrashedItem;
use Kwaadpepper\LaravelStorageManager\Service\TrashService;
use Psr\Log\LoggerInterface;

/**
 * @implements Action<DeletePathInput, ?TrashedItem>
 */
final readonly class DeletePathAction implements Action
{
    public function __construct(
        private PathNormalizer $pathNormalizer,
        private StorageManagerConfig $config,
        private StorageGateway $storageGateway,
        private TrashService $trashService,
        private EventDispatcher $eventDispatcher,
        private LoggerInterface $logger,
    ) {
    }

    public function ability(): string
    {
        return SmAbility::DELETE;
    }

    /**
     * @param  DeletePathInput  $input
     *
     * @throws FileOperationException
     */
    public function __invoke(ActionInput $input): ?TrashedItem
    {
        $input->disk->assertWritable();
        $this->logger->info('Deleting item', [
            'disk'  => $input->disk->name,
            'path'  => $input->path->value,
            'force' => $input->force,
        ]);

        if ($this->config->trash->enabled && ! $input->force) {
            return $this->trashService->moveToTrash($input->disk, $input->path);
        }

        if ($this->storageGateway->resolveIsDirectory($input->disk, $input->path)) {
            $this->deleteDirectory($input->disk, $input->path, $input->force);

            return null;
        }

        $this->deleteFile($input->disk, $input->path);

        return null;
    }

    /**
     * @throws FileOperationException
     */
    private function deleteDirectory(Disk $disk, Path $path, bool $force): void
    {
        $normalizedPath = $this->pathNormalizer->normalizePath($path->value);

        if ($this->pathNormalizer->isRootPath($normalizedPath)) {
            $this->logger->warning('Rejected deleteDirectory: cannot delete root path', [
                'disk' => $disk->name,
                'path' => $path->value,
            ]);
            FileOperationException::throwWith(FileOperationError::INVALID_PATH);
        }

        if (! $this->storageGateway->isDirectory($disk, $path)) {
            $this->logger->warning('Rejected deleteDirectory: directory not found', [
                'disk' => $disk->name,
                'path' => $path->value,
            ]);
            FileOperationException::throwWith(FileOperationError::DIRECTORY_NOT_FOUND);
        }

        if (! $force) {
            $files       = $this->storageGateway->files($disk, $normalizedPath);
            $directories = $this->storageGateway->directories($disk, $normalizedPath);
            if (count($files) > 0 || count($directories) > 0) {
                $this->logger->warning('Rejected deleteDirectory: directory not empty', [
                    'disk' => $disk->name,
                    'path' => $path->value,
                ]);
                FileOperationException::throwWith(FileOperationError::DIRECTORY_NOT_EMPTY);
            }
        }

        $this->eventDispatcher->dispatch(
            DirectoryDeleting::class,
            [
                'disk'  => $disk->name,
                'path'  => $path->value,
                'force' => $force,
            ]
        );

        if ($this->storageGateway->deleteDirectory($disk, $normalizedPath) === false) {
            $this->logger->error('Failed to delete directory', [
                'disk' => $disk->name,
                'path' => $path->value,
            ]);
            FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
        }

        $this->logger->info('Directory deleted successfully', [
            'disk' => $disk->name,
            'path' => $path->value,
        ]);
        $this->eventDispatcher->dispatch(
            DirectoryDeleted::class,
            ['path' => $path->value]
        );
    }

    /**
     * @throws FileOperationException
     */
    private function deleteFile(Disk $disk, Path $path): void
    {
        $normalizedPath = $this->pathNormalizer->normalizePath($path->value);

        if (! $this->storageGateway->isFile($disk, $path)) {
            $this->logger->warning('Rejected deleteFile: file not found', [
                'disk' => $disk->name,
                'path' => $path->value,
            ]);
            FileOperationException::throwWith(FileOperationError::FILE_NOT_FOUND);
        }

        $this->eventDispatcher->dispatch(
            FileDeleting::class,
            [
                'disk'  => $disk->name,
                'path'  => $path->value,
                'force' => true,
            ]
        );

        if ($this->storageGateway->delete($disk, $normalizedPath) === false) {
            $this->logger->error('Failed to delete file', [
                'disk' => $disk->name,
                'path' => $path->value,
            ]);
            FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
        }

        $this->logger->info('File deleted successfully', [
            'disk' => $disk->name,
            'path' => $path->value,
        ]);
        $this->eventDispatcher->dispatch(
            FileDeleted::class,
            ['path' => $path->value]
        );
    }
}
