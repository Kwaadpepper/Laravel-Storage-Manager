<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\FileManager;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;
use Kwaadpepper\LaravelStorageManager\Enum\GenericDomainError;
use Kwaadpepper\LaravelStorageManager\Exception\DomainException;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\FileStream;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\DirectoryPathProperties;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\FilePathProperties;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathList as PathContent;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathProperties;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeDirectory;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeFile;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeLevel;

class FileManager
{
    public function __construct(
        private readonly PathNormalizer $pathNormalizer,
        private readonly FilePropertyExtractor $filePropertyExtractor,
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

    public function getProperties(Path $path): PathProperties
    {
        $disk             = $this->getDisk();
        $normalizedPath   = $this->pathNormalizer->normalizePath($path->value);
        $sourceIsDir      = $this->isDirectory($path);
        $sourceIsFile     = $this->isFile($path);

        if (! $sourceIsDir && ! $sourceIsFile) {
            FileOperationException::throwWith(FileOperationError::FILE_NOT_FOUND);
        }

        $normalizedPath = new Path($normalizedPath);

        return $sourceIsDir
            ? $this->filePropertyExtractor->directoryProperties($disk, $normalizedPath)
            : $this->filePropertyExtractor->fileProperties($disk, $normalizedPath);
    }

    public function getContent(?Path $path = null): PathContent
    {
        $filesystem  = $this->getStorage();
        $directory   = $this->pathNormalizer->normalizePath($path ? $path->value : '/');
        $files       = array_map(
            fn ($file) => $this->pathNormalizer->normalizePath($file),
            $filesystem->files($directory)
        );
        $directories = array_map(
            fn ($dir) => $this->pathNormalizer->normalizePath($dir),
            $filesystem->directories($directory)
        );

        $files       = array_map(fn ($file) => new Path($file), $files);
        $directories = array_map(fn ($dir) => new Path($dir), $directories);

        return new PathContent(
            array_values($files),
            array_values($directories),
        );
    }

    public function getPathTree(?Path $path = null): PathTreeLevel
    {
        $filesystem  = $this->getStorage();
        $directory   = $this->pathNormalizer->normalizePath($path ? $path->value : '/');
        $directories = array_map(
            fn ($dir) => $this->pathNormalizer->normalizePath($dir),
            $filesystem->directories($directory)
        );

        $files = array_map(
            fn ($file) => $this->pathNormalizer->normalizePath($file),
            $filesystem->files($directory)
        );

        $directories = array_map(function ($dir) use ($filesystem) {
            $path = new Path($dir);
            $properties = $this->getProperties($path);

            if (! ($properties instanceof DirectoryPathProperties)) {
                throw new \LogicException("Expected directory properties for path '{$path}', got file properties.");
            }

            return new PathTreeDirectory(
                $path,
                ! empty($filesystem->directories((string) $path)),
                $properties->visibility
            );
        }, $directories);

        $files = array_map(function ($file) use ($filesystem) {
            $path       = new Path($file);
            $properties = $this->getProperties($path);

            if (! ($properties instanceof FilePathProperties)) {
                throw new \LogicException("Expected file properties for path '{$path}', got directory properties.");
            }

            try {
                $publicUrl = $filesystem->url(ltrim($path->value, '/'));
            } catch (\Throwable $e) {
                $publicUrl = null;
            }

            return new PathTreeFile(
                $path,
                $properties->size,
                $properties->extension,
                $properties->visibility,
                $publicUrl
            );
        }, $files);

        return new PathTreeLevel(
            array_values($directories),
            array_values($files)
        );
    }

    /**
     * @throws FileOperationException
     */
    public function createDirectory(Path $path): void
    {
        $this->assertNotReadOnly();

        $filesystem     = $this->getStorage();
        $normalizedPath = $this->pathNormalizer->normalizePath($path->value);

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
    public function deleteDirectory(Path $path, bool $force = false): void
    {
        $this->assertNotReadOnly();

        $filesystem     = $this->getStorage();
        $normalizedPath = $this->pathNormalizer->normalizePath($path->value);

        if (preg_match('/^\/?$/', $normalizedPath)) {
            FileOperationException::throwWith(FileOperationError::INVALID_PATH);
        }

        if (! $filesystem->exists($normalizedPath)) {
            FileOperationException::throwWith(FileOperationError::DIRECTORY_NOT_FOUND);
        }

        if (! $force) {
            $files = $filesystem->files($normalizedPath);
            $directories = $filesystem->directories($normalizedPath);
            if (count($files) > 0 || count($directories) > 0) {
                FileOperationException::throwWith(FileOperationError::DIRECTORY_NOT_EMPTY);
            }
        }

        if ($filesystem->deleteDirectory($normalizedPath) === false) {
            FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
        }
    }

    public function createFile(Path $path, string $content = ''): void
    {
        $this->assertNotReadOnly();

        $filesystem     = $this->getStorage();
        $normalizedPath = $this->pathNormalizer->normalizePath($path->value);

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
        $this->assertNotReadOnly();

        $filesystem     = $this->getStorage();
        $normalizedPath = $this->pathNormalizer->normalizePath($path->value);

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
    public function rename(Path $source, string $newName): Path
    {
        $this->assertNotReadOnly();

        $filesystem       = $this->getStorage();
        $normalizedSource = $this->pathNormalizer->normalizePath($source->value);
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

        if ($sourceIsDir && in_array($this->getDisk()->driver, ['s3', 'ftp'], true)) {
            $this->polyfillMoveDirectory($normalizedSource, $normalizedDestination);
        } else {
            if ($filesystem->move($normalizedSource, $normalizedDestination) === false) {
                FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
            }
        }

        return $destination;
    }

    /**
     * @throws FileOperationException
     */
    public function copy(Path $source, Path $destinationDir): Path
    {
        $this->assertNotReadOnly();

        $filesystem       = $this->getStorage();
        $normalizedSource = $this->pathNormalizer->normalizePath($source->value);
        $sourceIsDir      = $this->isDirectory($source);
        $sourceIsFile     = $this->isFile($source);

        if (! $sourceIsDir && ! $sourceIsFile) {
            FileOperationException::throwWith(FileOperationError::FILE_NOT_FOUND);
        }

        $basename    = basename($normalizedSource);
        $destination = Path::appendTo($destinationDir, $this->pathNormalizer->normalizePath($basename));

        $counter             = 1;
        $originalDestination = (string) $destination;
        while ($this->isDirectory($destination) || $this->isFile($destination)) {
            $pathInfo  = pathinfo($originalDestination);
            $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
            $filename  = $pathInfo['filename'];

            // Re-join dir with modified basename
            $newBasename = sprintf('%s (copy%s)%s', $filename, $counter > 1 ? ' ' . $counter : '', $extension);
            $destination = Path::appendTo($destinationDir, $this->pathNormalizer->normalizePath($newBasename));
            $counter++;
        }

        $normalizedDestination = (string) $destination;

        if ($sourceIsFile) {
            if ($filesystem->copy($normalizedSource, $normalizedDestination) === false) {
                FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
            }
        } else {
            if ($filesystem->makeDirectory($normalizedDestination) === false) {
                FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
            }

            $directories = $filesystem->allDirectories($normalizedSource);
            foreach ($directories as $dir) {
                $normalizedDir = $this->pathNormalizer->normalizePath($dir);
                $relativePath = substr($normalizedDir, strlen($normalizedSource) + 1);
                $filesystem->makeDirectory((string) Path::appendTo($destination, $relativePath));
            }

            $files = $filesystem->allFiles($normalizedSource);
            foreach ($files as $file) {
                $normalizedFile = $this->pathNormalizer->normalizePath($file);
                $relativePath = substr($normalizedFile, strlen($normalizedSource) + 1);
                $filesystem->copy($file, (string) Path::appendTo($destination, $relativePath));
            }
        }

        return $destination;
    }

    /**
     * @throws FileOperationException
     */
    public function move(Path $source, Path $destinationDir): void
    {
        $this->assertNotReadOnly();

        $filesystem       = $this->getStorage();
        $normalizedSource = $this->pathNormalizer->normalizePath($source->value);
        $sourceIsDir      = $this->isDirectory($source);
        $sourceIsFile     = $this->isFile($source);

        if (! $sourceIsDir && ! $sourceIsFile) {
            FileOperationException::throwWith(FileOperationError::FILE_NOT_FOUND);
        }

        $basename    = basename($normalizedSource);
        $destination = Path::appendTo($destinationDir, $this->pathNormalizer->normalizePath($basename));

        $counter             = 1;
        $originalDestination = (string) $destination;
        while ($this->isDirectory($destination) || $this->isFile($destination)) {
            $pathInfo  = pathinfo($originalDestination);
            $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
            $filename  = $pathInfo['filename'];

            $newBasename = sprintf('%s (copy%s)%s', $filename, $counter > 1 ? ' ' . $counter : '', $extension);
            $destination = Path::appendTo($destinationDir, $this->pathNormalizer->normalizePath($newBasename));
            $counter++;
        }

        $normalizedDestination = (string) $destination;

        if ($sourceIsDir && in_array($this->getDisk()->driver, ['s3', 'ftp'], true)) {
            $this->polyfillMoveDirectory($normalizedSource, $normalizedDestination);
        } else {
            if ($filesystem->move($normalizedSource, $normalizedDestination) === false) {
                FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
            }
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
        $normalizedPath = $this->pathNormalizer->normalizePath($path->value);

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
            $this->pathNormalizer->normalizePath($path->value)
        ) && ! $this->isDirectory($path);
    }

    /**
     * @throws FileOperationException
     */
    public function readStream(Path $path): FileStream
    {
        $filesystem     = $this->getStorage();
        $normalizedPath = $this->pathNormalizer->normalizePath($path->value);

        if (! $filesystem->exists($normalizedPath)) {
            FileOperationException::throwWith(FileOperationError::FILE_NOT_FOUND);
        }

        if ($this->isDirectory($path)) {
            FileOperationException::throwWith(FileOperationError::INVALID_PATH);
        }

        $stream = $filesystem->readStream($normalizedPath);

        if ($stream === null) {
            FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
        }

        return new FileStream($stream);
    }

    private function getDisk(): Disk
    {
        if ($this->activeDisk === null) {
            throw new \LogicException('No active disk set for FileManager.');
        }

        return $this->activeDisk;
    }

    private function assertNotReadOnly(): void
    {
        if ($this->getDisk()->readOnly) {
            FileOperationException::throwWith(FileOperationError::READ_ONLY_DISK);
        }
    }

    private function polyfillMoveDirectory(string $source, string $destination): void
    {
        $filesystem = $this->getStorage();

        if ($filesystem->makeDirectory($destination) === false) {
            FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
        }

        $directories = $filesystem->allDirectories($source);
        foreach ($directories as $dir) {
            $normalizedDir = $this->pathNormalizer->normalizePath($dir);
            $relativePath = substr($normalizedDir, strlen($source) + 1);
            $filesystem->makeDirectory((string) Path::appendTo(new Path($destination), $relativePath));
        }

        $files = $filesystem->allFiles($source);
        foreach ($files as $file) {
            $normalizedFile = $this->pathNormalizer->normalizePath($file);
            $relativePath = substr($normalizedFile, strlen($source) + 1);
            $filesystem->move($file, (string) Path::appendTo(new Path($destination), $relativePath));
        }

        $filesystem->deleteDirectory($source);
    }

    private function getStorage(): Filesystem
    {
        $disk = $this->getDisk();

        return Storage::disk($disk->name);
    }

    private function assertDiskExists(string $diskName): void
    {
        $disks = Arr::wrap(config('filesystems.disks', []));

        if (! isset($disks[$diskName])) {
            throw new DomainException(GenericDomainError::DISK_NOT_FOUND);
        }
    }
}
