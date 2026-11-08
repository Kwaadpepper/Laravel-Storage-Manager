<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Service;

use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathNormalizer;
use Kwaadpepper\LaravelStorageManager\Lib\StorageGateway;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\DirectoryListingResult;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;

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
        if (! $disk->isLocal()) {
            return $this->storageGateway->hasSubdirectories($disk, new Path($path));
        }

        $directories = $this->storageGateway->directories($disk, $path);

        return ! empty(array_filter(
            $directories,
            fn (string $dir): bool => ! $this->isTrashPath($this->pathNormalizer->normalizePath($dir))
        ));
    }

    public function listCleanContents(Disk $disk, string $directory): DirectoryListingResult
    {
        $allAttributes = $this->storageGateway->listContents($disk, $directory);

        $directories = [];
        $files       = [];

        foreach ($allAttributes as $attributes) {
            $normalized = $this->pathNormalizer->normalizePath($attributes->path());

            if ($this->isTrashPath($normalized)) {
                continue;
            }

            if ($attributes instanceof DirectoryAttributes) {
                $directories[] = $attributes;
            } elseif ($attributes instanceof FileAttributes) {
                $files[] = $attributes;
            }
        }

        return new DirectoryListingResult($directories, $files);
    }

    /**
     * @return array{directories: list<string>, files: list<string>}
     */
    public function listCleanEntries(Disk $disk, string $directory): array
    {
        $contents = $this->listCleanContents($disk, $directory);

        $directories = array_map(
            fn (DirectoryAttributes $dir): string => $this->pathNormalizer->normalizePath($dir->path()),
            $contents->directories
        );

        $files = array_map(
            fn (FileAttributes $file): string => $this->pathNormalizer->normalizePath($file->path()),
            $contents->files
        );

        return [
            'directories' => array_values($directories),
            'files'       => array_values($files),
        ];
    }
}
