<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\Storage;

use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\DiskStorageStats;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use League\Flysystem\StorageAttributes;

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

    /**
     * List contents of a directory with attributes in a single operation.
     *
     * @return list<StorageAttributes> | FileOperationException
     */
    public function listContents(Disk $disk, string $directory): array | FileOperationException;

    /**
     * Write contents to a file on disk.
     */
    public function put(Disk $disk, string $path, string $contents): true | FileOperationException;

    /**
     * Delete a file on disk.
     */
    public function delete(Disk $disk, string $path): true | FileOperationException;

    /**
     * Delete a directory on disk.
     */
    public function deleteDirectory(Disk $disk, string $path): true | FileOperationException;

    /**
     * Create a directory on disk.
     */
    public function makeDirectory(Disk $disk, string $path): true | FileOperationException;

    /**
     * Check if a file or directory exists on disk.
     */
    public function exists(Disk $disk, Path $path): bool;

    /**
     * Check if the given path is a directory.
     */
    public function isDirectory(Disk $disk, Path $path): bool;

    /**
     * Check if the given path is a file.
     */
    public function isFile(Disk $disk, Path $path): bool;

    /**
     * Retrieve the size of a file in bytes.
     */
    public function size(Disk $disk, string $path): int | FileOperationException;

    /**
     * List all files in a directory (non-recursive).
     *
     * @return list<string> | FileOperationException
     */
    public function files(Disk $disk, string $directory = ''): array | FileOperationException;

    /**
     * List all directories in a directory (non-recursive).
     *
     * @return list<string> | FileOperationException
     */
    public function directories(Disk $disk, string $directory = ''): array | FileOperationException;

    /**
     * List all files in a directory recursively.
     *
     * @return list<string> | FileOperationException
     */
    public function allFiles(Disk $disk, string $directory = ''): array | FileOperationException;

    /**
     * List all directories in a directory recursively.
     *
     * @return list<string> | FileOperationException
     */
    public function allDirectories(Disk $disk, string $directory = ''): array | FileOperationException;

    /**
     * Open a read stream for a file.
     *
     * @return resource | FileOperationException
     */
    public function readStream(Disk $disk, Path $path): mixed;

    /**
     * Write a stream resource to a path.
     *
     * @param  resource  $resource
     */
    public function writeStream(Disk $disk, Path $path, mixed $resource): true | FileOperationException;

    /**
     * Return the local filesystem absolute path if supported by driver, or null.
     */
    public function localPath(Disk $disk, string $path = ''): ?string;

    /**
     * Determine whether a directory has subdirectories without heavy overhead.
     */
    public function hasSubdirectories(Disk $disk, string $path): bool;
}
