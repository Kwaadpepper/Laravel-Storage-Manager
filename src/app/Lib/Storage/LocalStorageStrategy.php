<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\Storage;

use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Filesystem\FilesystemAdapter;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathNormalizer;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\DiskStorageStats;
use Psr\Log\LoggerInterface;

readonly class LocalStorageStrategy extends BaseStorageStrategy
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
        $this->logger->info(sprintf('Moving local file on disk "%s" from "%s" to "%s"', $disk->name, $source, $destination));

        $storage = $this->filesystemFactory->disk($disk->name);

        try {
            $moved = $storage->move($source, $destination);
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('Failed to move local file on disk "%s" from "%s" to "%s"', $disk->name, $source, $destination), [
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
        $this->logger->info(sprintf('Moving local directory on disk "%s" from "%s" to "%s"', $disk->name, $source, $destination));

        $storage = $this->filesystemFactory->disk($disk->name);

        try {
            $moved = $storage->move($source, $destination);
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('Failed to move local directory on disk "%s" from "%s" to "%s"', $disk->name, $source, $destination), [
                'exception' => $e,
            ]);
            $moved = false;
        }

        if (! $moved) {
            FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
        }
    }

    #[\Override]
    public function copyFile(Disk $disk, string $source, string $destination): void
    {
        $this->logger->info(sprintf('Copying local file on disk "%s" from "%s" to "%s"', $disk->name, $source, $destination));

        $storage = $this->filesystemFactory->disk($disk->name);

        try {
            $copied = $storage->copy($source, $destination);
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('Failed to copy local file on disk "%s" from "%s" to "%s"', $disk->name, $source, $destination), [
                'exception' => $e,
            ]);
            $copied = false;
        }

        if (! $copied) {
            FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
        }
    }

    #[\Override]
    public function copyDirectory(Disk $disk, string $source, string $destination): void
    {
        $this->logger->info(sprintf('Copying local directory on disk "%s" from "%s" to "%s"', $disk->name, $source, $destination));

        $storage = $this->filesystemFactory->disk($disk->name);

        if ($storage->makeDirectory($destination) === false) {
            $this->logger->error(sprintf('Failed to create destination directory "%s" on disk "%s"', $destination, $disk->name));
            FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
        }

        /** @var list<string> $directories */
        $directories = $storage->allDirectories($source);
        foreach ($directories as $dir) {
            $normalizedDir = $this->pathNormalizer->normalizePath($dir);
            $relativePath  = substr($normalizedDir, strlen('/' . $source) + 1);
            $targetDir     = ltrim($destination . '/' . $relativePath, '/');
            if ($storage->makeDirectory($targetDir) === false) {
                $this->logger->error(sprintf('Failed to create subdirectory "%s" on disk "%s"', $targetDir, $disk->name));
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

    #[\Override]
    public function supportsDirectoryMetadata(Disk $disk): bool
    {
        return true;
    }

    #[\Override]
    public function calculateDiskUsedBytes(Disk $disk): int
    {
        return $this->computeFlysystemUsedBytes($this->filesystemFactory->disk($disk->name));
    }

    #[\Override]
    public function getDiskStats(Disk $disk): DiskStorageStats
    {
        $storage = $this->filesystemFactory->disk($disk->name);

        if ($storage instanceof FilesystemAdapter) {
            try {
                $localPath = $storage->path('');

                if (is_dir($localPath)) {
                    $totalSpace = disk_total_space($localPath);
                    $freeSpace  = disk_free_space($localPath);

                    if ($totalSpace !== false && $freeSpace !== false && $totalSpace > 0) {
                        $totalInt   = (int) $totalSpace;
                        $freeInt    = (int) $freeSpace;
                        $usedInt    = max(0, $totalInt - $freeInt);
                        $percentage = round(($usedInt / $totalInt) * 100, 1);

                        return new DiskStorageStats(
                            mode: 'partition',
                            total: $totalInt,
                            free: $freeInt,
                            used: $usedInt,
                            percentage: $percentage,
                        );
                    }
                }
            } catch (\Throwable $diskEx) {
                unset($diskEx);
            }
        }

        $used = $this->calculateDiskUsedBytes($disk);

        return new DiskStorageStats(
            mode: 'folder',
            total: null,
            free: null,
            used: $used,
            percentage: null,
        );
    }
}
