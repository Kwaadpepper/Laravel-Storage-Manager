<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\FileManager;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
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
        $directory   = $path ? (string) $path : '/';
        $files       = $filesystem->files($directory);
        $directories = $filesystem->directories($directory);

        $directoryPrefix = rtrim($directory, '/') . '/';

        return new PathContent(
            files: array_map(fn ($file) => new Path("{$directoryPrefix}{$file}"), $files),
            directories: array_map(fn ($dir) => new Path("{$directoryPrefix}{$dir}"), $directories),
        );
    }

    public function getPathTree(?Path $path = null): PathTreeLevel
    {
        $filesystem  = $this->getStorage();
        $directory   = $path ? (string) $path : '/';
        $directories = $filesystem->directories($directory);

        $directoryPrefix = rtrim($directory, '/') . '/';

        return new PathTreeLevel(
            directories: array_map(fn ($dir) => new PathTreeDirectory(
                "{$directoryPrefix}{$dir}",
                ! empty($filesystem->directories("{$directoryPrefix}{$dir}"))
            ), $directories),
        );
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
