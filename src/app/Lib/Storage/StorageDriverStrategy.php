<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\Storage;

use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;

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
}
