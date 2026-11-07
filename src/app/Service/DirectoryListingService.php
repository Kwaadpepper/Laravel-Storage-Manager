<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Service;

use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathNormalizer;
use Kwaadpepper\LaravelStorageManager\Lib\StorageGateway;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;

final readonly class DirectoryListingService
{
    public function __construct(
        private PathNormalizer $pathNormalizer,
        private StorageGateway $storageGateway,
        private StorageManagerConfig $config,
    ) {
    }

    public function isTrashPath(string $normalizedPath): bool
    {
        $trashFolder = '/' . trim($this->config->trash->folder, '/');

        return $normalizedPath === $trashFolder || str_starts_with($normalizedPath, $trashFolder . '/');
    }

    public function hasSubdirectories(Disk $disk, string $path): bool
    {
        $directories = $this->storageGateway->directories($disk, $path);

        return ! empty(array_filter(
            $directories,
            fn (string $dir): bool => ! $this->isTrashPath($this->pathNormalizer->normalizePath($dir))
        ));
    }

    /**
     * @return array{directories: list<string>, files: list<string>}
     */
    public function listCleanEntries(Disk $disk, string $directory): array
    {
        $rawDirectories = $this->storageGateway->directories($disk, $directory);
        $rawFiles       = $this->storageGateway->files($disk, $directory);

        $directories = array_map(
            fn (string $dir): string => $this->pathNormalizer->normalizePath($dir),
            $rawDirectories
        );

        $files = array_map(
            fn (string $file): string => $this->pathNormalizer->normalizePath($file),
            $rawFiles
        );

        $directories = array_values(array_filter($directories, fn (string $dir): bool => ! $this->isTrashPath($dir)));
        $files       = array_values(array_filter($files, fn (string $file): bool => ! $this->isTrashPath($file)));

        return [
            'directories' => $directories,
            'files'       => $files,
        ];
    }
}
