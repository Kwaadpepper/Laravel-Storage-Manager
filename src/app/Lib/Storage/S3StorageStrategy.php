<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\Storage;

use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathNormalizer;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\DiskStorageStats;
use Psr\Log\LoggerInterface;

readonly class S3StorageStrategy extends BaseStorageStrategy
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
        $this->logger->info(sprintf('Moving S3 object on disk "%s" from "%s" to "%s"', $disk->name, $source, $destination));

        $this->copyFile($disk, $source, $destination);

        $storage = $this->filesystemFactory->disk($disk->name);

        try {
            $deleted = $storage->delete($source);
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('Failed to delete source S3 object "%s" on disk "%s" after copy', $source, $disk->name), [
                'exception' => $e,
            ]);
            $deleted = false;
        }

        if (! $deleted) {
            FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
        }
    }

    #[\Override]
    public function moveDirectory(Disk $disk, string $source, string $destination): void
    {
        $this->logger->info(sprintf('Moving S3 directory prefix on disk "%s" from "%s" to "%s"', $disk->name, $source, $destination));

        $this->copyDirectory($disk, $source, $destination);

        $storage = $this->filesystemFactory->disk($disk->name);
        if ($storage->deleteDirectory($source) === false) {
            $this->logger->error(sprintf('Failed to delete source S3 directory prefix "%s" on disk "%s"', $source, $disk->name));
            FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
        }
    }

    #[\Override]
    public function copyFile(Disk $disk, string $source, string $destination): void
    {
        $this->logger->info(sprintf('Copying S3 object on disk "%s" from "%s" to "%s"', $disk->name, $source, $destination));

        $storage = $this->filesystemFactory->disk($disk->name);

        if ($storage instanceof AwsS3V3Adapter) {
            $copied = $this->serverSideCopy($storage, $source, $destination);
        } else {
            try {
                $copied = $storage->copy($source, $destination);
            } catch (\Throwable $e) {
                $this->logger->error(sprintf('Failed to copy object on S3 disk "%s" from "%s" to "%s"', $disk->name, $source, $destination), [
                    'exception' => $e,
                ]);
                $copied = false;
            }
        }

        if (! $copied) {
            FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
        }
    }

    #[\Override]
    public function copyDirectory(Disk $disk, string $source, string $destination): void
    {
        $this->logger->info(sprintf('Copying S3 directory prefix on disk "%s" from "%s" to "%s"', $disk->name, $source, $destination));

        $storage = $this->filesystemFactory->disk($disk->name);

        if ($storage->makeDirectory($destination) === false) {
            $this->logger->error(sprintf('Failed to create S3 directory prefix "%s" on disk "%s"', $destination, $disk->name));
            FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
        }

        /** @var list<string> $directories */
        $directories = $storage->allDirectories($source);
        foreach ($directories as $dir) {
            $normalizedDir = $this->pathNormalizer->normalizePath($dir);
            $relativePath  = substr($normalizedDir, strlen('/' . $source) + 1);
            $targetDir     = ltrim($destination . '/' . $relativePath, '/');
            if ($storage->makeDirectory($targetDir) === false) {
                $this->logger->error(sprintf('Failed to create S3 subdirectory prefix "%s" on disk "%s"', $targetDir, $disk->name));
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

    private function serverSideCopy(AwsS3V3Adapter $storage, string $source, string $destination): bool
    {
        /** @var array<string, mixed> $config */
        $config  = $storage->getConfig();
        $bucket  = is_string($config['bucket'] ?? null) ? $config['bucket'] : '';
        $srcKey  = $storage->path($source);
        $destKey = $storage->path($destination);

        $encodedKey = implode('/', array_map(
            fn (string $segment): string => rawurlencode($segment),
            explode('/', ltrim($srcKey, '/'))
        ));

        try {
            $storage->getClient()->copyObject([
                'Bucket'     => $bucket,
                'Key'        => $destKey,
                'CopySource' => '/' . $bucket . '/' . $encodedKey,
            ]);

            return true;
        } catch (\Throwable) {
            try {
                $storage->getClient()->copyObject([
                    'Bucket'     => $bucket,
                    'Key'        => $destKey,
                    'CopySource' => $bucket . '/' . $encodedKey,
                ]);

                return true;
            } catch (\Throwable $e2) {
                $this->logger->error(sprintf('S3 server-side copy failed from "%s" to "%s"', $source, $destination), [
                    'exception' => $e2,
                ]);

                return false;
            }
        }
    }

    #[\Override]
    public function supportsDirectoryMetadata(Disk $disk): bool
    {
        return false;
    }

    #[\Override]
    public function calculateDiskUsedBytes(Disk $disk): int
    {
        return $this->computeFlysystemUsedBytes($this->filesystemFactory->disk($disk->name));
    }

    #[\Override]
    public function getDiskStats(Disk $disk): DiskStorageStats
    {
        return new DiskStorageStats(
            mode: 'unlimited',
        );
    }
}
