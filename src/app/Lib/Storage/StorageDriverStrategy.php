<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\Storage;

use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\DiskStorageStats;

interface StorageDriverStrategy
{
    /**
     * @throws FileOperationException
     */
    public function moveFile(Disk $disk, string $source, string $destination): void;

    /**
     * @throws FileOperationException
     */
    public function moveDirectory(Disk $disk, string $source, string $destination): void;

    /**
     * @throws FileOperationException
     */
    public function copyFile(Disk $disk, string $source, string $destination): void;

    /**
     * @throws FileOperationException
     */
    public function copyDirectory(Disk $disk, string $source, string $destination): void;

    /**
     * Determine whether the storage driver supports metadata on directories (e.g. timestamp, visibility).
     */
    public function supportsDirectoryMetadata(Disk $disk): bool;

    /**
     * Calculate the total used bytes for the given disk.
     */
    public function calculateDiskUsedBytes(Disk $disk): int;

    /**
     * Retrieve the driver-level disk space and capacity statistics.
     */
    public function getDiskStats(Disk $disk): DiskStorageStats;
}
