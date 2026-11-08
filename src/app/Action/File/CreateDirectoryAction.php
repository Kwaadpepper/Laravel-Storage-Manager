<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Action\File;

use Kwaadpepper\LaravelStorageManager\Acl\SmAbility;
use Kwaadpepper\LaravelStorageManager\Contract\Action;
use Kwaadpepper\LaravelStorageManager\Contract\ActionInput;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;
use Kwaadpepper\LaravelStorageManager\Event\DirectoryCreated;
use Kwaadpepper\LaravelStorageManager\Event\DirectoryCreating;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Lib\Event\EventDispatcher;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathNormalizer;
use Kwaadpepper\LaravelStorageManager\Lib\StorageGateway;
use Psr\Log\LoggerInterface;

/**
 * @implements Action<CreateDirectoryInput, void>
 */
final readonly class CreateDirectoryAction implements Action
{
    public function __construct(
        private PathNormalizer $pathNormalizer,
        private StorageGateway $storageGateway,
        private EventDispatcher $eventDispatcher,
        private LoggerInterface $logger,
    ) {
    }

    public function ability(): string
    {
        return SmAbility::CREATE_DIRECTORY;
    }

    /**
     * @param  CreateDirectoryInput  $input
     *
     * @throws FileOperationException
     */
    public function __invoke(ActionInput $input): void
    {
        $input->disk->assertWritable();

        $normalizedPath = $this->pathNormalizer->normalizePath($input->path->value);

        if ($this->storageGateway->exists($input->disk, $input->path)) {
            $this->logger->warning('Rejected createDirectory: directory already exists', [
                'disk' => $input->disk->name,
                'path' => $input->path->value,
            ]);
            FileOperationException::throwWith(FileOperationError::DIRECTORY_ALREADY_EXISTS);
        }

        $this->eventDispatcher->dispatch(
            DirectoryCreating::class,
            [
                'disk' => $input->disk->name,
                'path' => $input->path->value,
            ]
        );

        if ($this->storageGateway->makeDirectory($input->disk, $normalizedPath) === false) {
            $this->logger->error('Failed to create directory', [
                'disk' => $input->disk->name,
                'path' => $input->path->value,
            ]);
            FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
        }

        $this->logger->info('Directory created successfully', [
            'disk' => $input->disk->name,
            'path' => $input->path->value,
        ]);
        $this->eventDispatcher->dispatch(
            DirectoryCreated::class,
            ['path' => $input->path->value]
        );
    }
}
