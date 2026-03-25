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

    /**
     * @throws FileOperationException
     */
    public function rename(Path $source, string $newName): void
    {
        $filesystem       = $this->getStorage();
        $normalizedSource = $this->pathNormalizer->normalizePath((string) $source);
        $sourceIsDir      = $this->isDirectory($source);
        $sourceIsFile     = $this->isFile($source);

        if (! $sourceIsDir && ! $sourceIsFile) {
            FileOperationException::throwWith(FileOperationError::FILE_NOT_FOUND);
        }

        $destination          = Path::appendTo(
            new Path(dirname($normalizedSource)),
            $this->pathNormalizer->normalizePath($newName)
        );
        $normalizedDestination = (string) $destination;

        if ($this->isDirectory($destination) || $this->isFile($destination)) {
            FileOperationException::throwWith(
                $sourceIsDir
                    ? FileOperationError::DIRECTORY_ALREADY_EXISTS
                    : FileOperationError::FILE_ALREADY_EXISTS
            );
        }

        if ($filesystem->move($normalizedSource, $normalizedDestination) === false) {
            FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
        }
    }

    public function exists(Path $path): bool
    {
        return $this->getStorage()->exists(
            $this->pathNormalizer->normalizePath((string) $path)
        );
    }

    public function isDirectory(Path $path): bool
    {
        $normalizedPath = $this->pathNormalizer->normalizePath((string) $path);

        if ($normalizedPath === '/') {
            return true;
        }

        $filesystem  = $this->getStorage();
        $parentDir   = dirname($normalizedPath);
        $directories = array_map(
            fn ($dir) => $this->pathNormalizer->normalizePath($dir),
            $filesystem->directories($parentDir)
        );

        return in_array($normalizedPath, $directories, true);
    }

    public function isFile(Path $path): bool
    {
        return $this->getStorage()->exists(
            $this->pathNormalizer->normalizePath((string) $path)
        ) && ! $this->isDirectory($path);
    }

    private function getStorage(): Filesystem
    {
        return Storage::disk($this->activeDisk->name);
    }

    private function assertDiskExists(string $diskName): void
    {
        $disks = config('filesystems.disks');

        if (! isset($disks[$diskName])) {
            throw new \InvalidArgumentException("Disk '{$diskName}' does not exist.");
        }
    }
}
