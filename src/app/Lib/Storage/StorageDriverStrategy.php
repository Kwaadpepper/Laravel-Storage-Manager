<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\Storage;

use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\DiskStorageStats;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;

interface StorageDriverStrategy
{
    /**
     * Move a file from source to destination.
     */
    public function moveFile(Disk $disk, string $source, string $destination): true | FileOperationException;

    /**
     * Move a directory from source to destination.
     */
    public function moveDirectory(Disk $disk, string $source, string $destination): true | FileOperationException;

    /**
     * Copy a file from source to destination.
     */
    public function copyFile(Disk $disk, string $source, string $destination): true | FileOperationException;

    /**
     * Copy a directory recursively from source to destination.
     */
    public function copyDirectory(Disk $disk, string $source, string $destination): true | FileOperationException;

    /**
     * Determine whether the storage driver supports metadata on directories (e.g. timestamp, visibility).
     */
    public function supportsDirectoryMetadata(Disk $disk): bool;

    /**
     * Determine whether the storage driver supports generating public URLs.
     */
    public function supportsPublicUrl(Disk $disk): bool;

    /**
     * Retrieve file content or return an exception on error / not found.
     */
    public function get(Disk $disk, string $path): string | FileOperationException;

    /**
     * Retrieve last modified timestamp or return an exception on error / unsupported.
     */
    public function lastModified(Disk $disk, Path $path): int | FileOperationException;

    /**
     * Retrieve visibility or return an exception on error / unsupported.
     */
    public function visibility(Disk $disk, Path $path): string | FileOperationException;

    /**
     * Retrieve MIME type or return an exception on error / unsupported.
     */
    public function mimeType(Disk $disk, Path $path): string | FileOperationException;

    /**
     * Retrieve public URL or return an exception on error / unsupported.
     */
    public function publicUrl(Disk $disk, Path $path): string | FileOperationException;

    /**
     * Calculate the total used bytes for the given disk or return an exception on I/O failure.
     */
    public function calculateDiskUsedBytes(Disk $disk): int | FileOperationException;

    /**
     * Retrieve the driver-level disk space and capacity statistics.
     */
    public function getDiskStats(Disk $disk): DiskStorageStats | FileOperationException;
}
