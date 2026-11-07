<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Action\File;

use Kwaadpepper\LaravelStorageManager\Acl\SmAbility;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Contract\Action;
use Kwaadpepper\LaravelStorageManager\Contract\ActionInput;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;
use Kwaadpepper\LaravelStorageManager\Event\FileCreated;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Lib\Event\EventDispatcher;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathNormalizer;
use Kwaadpepper\LaravelStorageManager\Lib\Security\MimeTypeChecker;
use Kwaadpepper\LaravelStorageManager\Lib\StorageGateway;
use Psr\Log\LoggerInterface;

/**
 * @implements Action<CreateFileInput, void>
 */
final readonly class CreateFileAction implements Action
{
    public function __construct(
        private PathNormalizer $pathNormalizer,
        private StorageManagerConfig $config,
        private StorageGateway $storageGateway,
        private MimeTypeChecker $mimeTypeChecker,
        private EventDispatcher $eventDispatcher,
        private LoggerInterface $logger,
    ) {
    }

    public function ability(): string
    {
        return SmAbility::CREATE_FILE;
    }

    /**
     * @param  CreateFileInput  $input
     *
     * @throws FileOperationException
     */
    public function __invoke(ActionInput $input): void
    {
        $input->disk->assertWritable();

        $normalizedPath = $this->pathNormalizer->normalizePath($input->path->value);
        $extension      = strtolower(pathinfo($normalizedPath, PATHINFO_EXTENSION));

        if (! $this->config->upload->isExtensionAllowed($extension)) {
            $this->logger->warning('Rejected createFile: forbidden extension', [
                'disk'      => $input->disk->name,
                'path'      => $input->path->value,
                'extension' => $extension,
            ]);
            FileOperationException::throwWith(FileOperationError::FORBIDDEN_FILE_EXTENSION);
        }

        if ($input->content !== '') {
            $detectedMime = $this->mimeTypeChecker->detectBufferMimeType($input->content);
            if (! $this->mimeTypeChecker->isMimeAllowedForExtension($extension, $detectedMime)) {
                $this->logger->warning('Rejected createFile: forbidden mime type for extension', [
                    'disk'      => $input->disk->name,
                    'path'      => $input->path->value,
                    'extension' => $extension,
                    'mime'      => $detectedMime,
                ]);
                FileOperationException::throwWith(FileOperationError::FORBIDDEN_FILE_MIME);
            }
        }

        if ($this->storageGateway->exists($input->disk, $input->path)) {
            $this->logger->warning('Rejected createFile: file already exists', [
                'disk' => $input->disk->name,
                'path' => $input->path->value,
            ]);
            FileOperationException::throwWith(FileOperationError::FILE_ALREADY_EXISTS);
        }

        if ($this->storageGateway->put($input->disk, $normalizedPath, $input->content) === false) {
            $this->logger->error('Failed to create file', [
                'disk' => $input->disk->name,
                'path' => $input->path->value,
            ]);
            FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
        }

        $this->logger->info('File created successfully', [
            'disk' => $input->disk->name,
            'path' => $input->path->value,
        ]);
        $this->eventDispatcher->dispatch(
            FileCreated::class,
            ['path' => $input->path->value]
        );
    }
}
