<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\FileManager;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathList as PathContent;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeDirectory;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeLevel;

class FileManager
{
    private ?Disk $activeDisk;

    public function __construct(?Disk $disk)
    {
        $this->setActiveDisk($disk);
    }

    public function setActiveDisk(?Disk $disk): void
    {
        $this->activeDisk = $disk;

        if ($disk !== null) {
            $this->assertDiskExists($disk->name);
        }
    }

    public function getContent(?Path $path = null): PathContent
    {
        $filesystem  = $this->getStorage();
        $directory   = $this->normalizePath($path ? (string) $path : '/');
        $files       = array_map(
            fn ($file) => $this->normalizePath($file),
            $filesystem->files($directory)
        );
        $directories = array_map(
            fn ($dir) => $this->normalizePath($dir),
            $filesystem->directories($directory)
        );

        return new PathContent(
            files: array_map(fn ($file) => new Path($file), $files),
            directories: array_map(fn ($dir) => new Path($dir), $directories),
        );
    }

    public function getPathTree(?Path $path = null): PathTreeLevel
    {
        $filesystem  = $this->getStorage();
        $directory   = $this->normalizePath($path ? (string) $path : '/');
        $directories = array_map(
            fn ($dir) => $this->normalizePath($dir),
            $filesystem->directories($directory)
        );

        return new PathTreeLevel(
            directories: array_map(fn ($dir) => new PathTreeDirectory(
                (string) new Path($dir),
                ! empty($filesystem->directories((string) new Path($dir)))
            ), $directories),
        );
    }

    /**
     * @throws FileOperationException
     */
    public function delete(Path $path): void
    {
        $filesystem     = $this->getStorage();
        $normalizedPath = $this->normalizePath((string) $path);

        if (! $filesystem->exists($normalizedPath)) {
            return;
        }

        if ($filesystem->delete($normalizedPath) === false) {
            throw new FileOperationException("Failed to delete the path '{$normalizedPath}'.");
        }
    }

    private function normalizePath(string $path): string
    {
        return '/' . ltrim(str_replace('\\', '/', $path), '/');
    }

    private function getStorage(): Filesystem
    {
        return Storage::disk($this->activeDisk?->name ?? null);
    }

    private function assertDiskExists(string $diskName): void
    {
        $disks = config('filesystems.disks');

        if (! isset($disks[$diskName])) {
            throw new \InvalidArgumentException("Disk '{$diskName}' does not exist.");
        }
    }
}
