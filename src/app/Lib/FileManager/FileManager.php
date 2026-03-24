<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\FileManager;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationError;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathList as PathContent;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeDirectory;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeLevel;

class FileManager
{
    public function __construct(
        private readonly PathNormalizer $pathNormalizer,
        private ?Disk $activeDisk
    ) {
        $this->setActiveDisk($activeDisk);
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
        $directory   = $this->pathNormalizer->normalizePath($path ? (string) $path : '/');
        $files       = array_map(
            fn ($file) => $this->pathNormalizer->normalizePath($file),
            $filesystem->files($directory)
        );
        $directories = array_map(
            fn ($dir) => $this->pathNormalizer->normalizePath($dir),
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
        $directory   = $this->pathNormalizer->normalizePath($path ? (string) $path : '/');
        $directories = array_map(
            fn ($dir) => $this->pathNormalizer->normalizePath($dir),
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
    public function createDirectory(Path $path): void
    {
        $filesystem     = $this->getStorage();
        $normalizedPath = $this->pathNormalizer->normalizePath((string) $path);

        if ($filesystem->exists($normalizedPath)) {
            FileOperationException::throwWith(FileOperationError::DIRECTORY_ALREADY_EXISTS);
        }

        if ($filesystem->makeDirectory($normalizedPath) === false) {
            FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
        }
    }

    /**
     * @throws FileOperationException
     */
    public function deleteDirectory(Path $path): void
    {
        $filesystem     = $this->getStorage();
        $normalizedPath = $this->pathNormalizer->normalizePath((string) $path);

        if (preg_match('/^\/?$/', $normalizedPath)) {
            FileOperationException::throwWith(FileOperationError::INVALID_PATH);
        }

        if (! $filesystem->exists($normalizedPath)) {
            FileOperationException::throwWith(FileOperationError::DIRECTORY_NOT_FOUND);
        }

        if ($filesystem->deleteDirectory($normalizedPath) === false) {
            FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
        }
    }

    public function createFile(Path $path, string $content = ''): void
    {
        $filesystem     = $this->getStorage();
        $normalizedPath = $this->pathNormalizer->normalizePath((string) $path);

        if ($filesystem->exists($normalizedPath)) {
            FileOperationException::throwWith(FileOperationError::FILE_ALREADY_EXISTS);
        }

        // Create an empty file
        $filesystem->put($normalizedPath, $content);
    }

    /**
     * @throws FileOperationException
     */
    public function deleteFile(Path $path): void
    {
        $filesystem     = $this->getStorage();
        $normalizedPath = $this->pathNormalizer->normalizePath((string) $path);

        if (! $filesystem->exists($normalizedPath)) {
            FileOperationException::throwWith(FileOperationError::FILE_NOT_FOUND);
        }

        if ($filesystem->delete($normalizedPath) === false) {
            FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
        }
    }

    public function isDirectory(Path $path): bool
    {
        $filesystem     = $this->getStorage();
        $normalizedPath = $this->pathNormalizer->normalizePath((string) $path);

        if (! empty($filesystem->directories($normalizedPath)) || ! empty($filesystem->files($normalizedPath))) {
            return true;
        }

        $withSlash = rtrim($normalizedPath, '/') . '/';

        return $filesystem->exists($withSlash);
    }

    public function isFile(Path $path): bool
    {
        $filesystem     = $this->getStorage();
        $normalizedPath = $this->pathNormalizer->normalizePath((string) $path);

        if (! $filesystem->exists($normalizedPath)) {
            return false;
        }

        return empty($filesystem->directories($normalizedPath)) && empty($filesystem->files($normalizedPath));
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
