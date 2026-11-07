<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\Storage;

use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathNormalizer;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Psr\Log\LoggerInterface;

readonly class RemoteStorageStrategy implements StorageDriverStrategy
{
    public function __construct(
        private FilesystemFactory $filesystemFactory,
        private PathNormalizer $pathNormalizer,
        private LoggerInterface $logger,
    ) {
    }

    #[\Override]
    public function moveFile(Disk $disk, string $source, string $destination): void
    {
        $this->logger->info(sprintf('Moving remote file on disk "%s" (%s) from "%s" to "%s"', $disk->name, $disk->driver, $source, $destination));

        $storage = $this->filesystemFactory->disk($disk->name);

        try {
            $moved = $storage->move($source, $destination);
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('Failed to move remote file on disk "%s" from "%s" to "%s"', $disk->name, $source, $destination), [
                'exception' => $e,
            ]);
            $moved = false;
        }

        if (! $moved) {
            FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
        }
    }

    #[\Override]
    public function moveDirectory(Disk $disk, string $source, string $destination): void
    {
        $this->logger->info(sprintf(
            'Moving remote directory on disk "%s" (%s) from "%s" to "%s"',
            $disk->name,
            $disk->driver,
            $source,
            $destination
        ));

        $storage = $this->filesystemFactory->disk($disk->name);

        if ($storage->makeDirectory($destination) === false) {
            $this->logger->error(sprintf('Failed to create remote directory "%s" on disk "%s"', $destination, $disk->name));
            FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
        }

        /** @var list<string> $directories */
        $directories = $storage->allDirectories($source);
        foreach ($directories as $dir) {
            $normalizedDir = $this->pathNormalizer->normalizePath($dir);
            $relativePath  = substr($normalizedDir, strlen('/' . $source) + 1);
            $targetDir     = ltrim($destination . '/' . $relativePath, '/');
            if ($storage->makeDirectory($targetDir) === false) {
                $this->logger->error(sprintf('Failed to create remote subdirectory "%s" on disk "%s"', $targetDir, $disk->name));
                FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
            }
        }

        /** @var list<string> $files */
        $files = $storage->allFiles($source);
        foreach ($files as $file) {
            $normalizedFile = $this->pathNormalizer->normalizePath($file);
            $relativePath   = substr($normalizedFile, strlen('/' . $source) + 1);
            $targetFile     = ltrim($destination . '/' . $relativePath, '/');
            $this->moveFile($disk, $file, $targetFile);
        }

        if ($storage->deleteDirectory($source) === false) {
            $this->logger->error(sprintf('Failed to delete remote source directory "%s" on disk "%s"', $source, $disk->name));
            FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
        }
    }

    #[\Override]
    public function copyFile(Disk $disk, string $source, string $destination): void
    {
        $this->logger->info(sprintf('Copying remote file on disk "%s" (%s) from "%s" to "%s"', $disk->name, $disk->driver, $source, $destination));

        $storage = $this->filesystemFactory->disk($disk->name);

        if (! $this->streamCopy($storage, $disk, $source, $destination)) {
            FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
        }
    }

    #[\Override]
    public function copyDirectory(Disk $disk, string $source, string $destination): void
    {
        $this->logger->info(sprintf(
            'Copying remote directory on disk "%s" (%s) from "%s" to "%s"',
            $disk->name,
            $disk->driver,
            $source,
            $destination
        ));

        $storage = $this->filesystemFactory->disk($disk->name);

        if ($storage->makeDirectory($destination) === false) {
            $this->logger->error(sprintf('Failed to create remote destination directory "%s" on disk "%s"', $destination, $disk->name));
            FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
        }

        /** @var list<string> $directories */
        $directories = $storage->allDirectories($source);
        foreach ($directories as $dir) {
            $normalizedDir = $this->pathNormalizer->normalizePath($dir);
            $relativePath  = substr($normalizedDir, strlen('/' . $source) + 1);
            $targetDir     = ltrim($destination . '/' . $relativePath, '/');
            if ($storage->makeDirectory($targetDir) === false) {
                $this->logger->error(sprintf('Failed to create remote subdirectory "%s" on disk "%s"', $targetDir, $disk->name));
                FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
            }
        }

        /** @var list<string> $files */
        $files = $storage->allFiles($source);
        foreach ($files as $file) {
            $normalizedFile = $this->pathNormalizer->normalizePath($file);
            $relativePath   = substr($normalizedFile, strlen('/' . $source) + 1);
            $targetFilePath = ltrim($destination . '/' . $relativePath, '/');
            $this->copyFile($disk, $file, $targetFilePath);
        }
    }

    private function streamCopy(Filesystem $storage, Disk $disk, string $source, string $destination): bool
    {
        try {
            $stream = $storage->readStream($source);
            if ($stream === false || $stream === null) {
                $this->logger->error(sprintf('Failed to open read stream for "%s" on remote disk "%s"', $source, $disk->name));

                return false;
            }

            $written = $storage->writeStream($destination, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }

            return $written !== false;
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('Failed to stream copy on remote disk "%s" from "%s" to "%s"', $disk->name, $source, $destination), [
                'exception' => $e,
            ]);

            return false;
        }
    }
}
