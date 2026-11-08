<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\Storage;

use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Filesystem\FilesystemAdapter;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Exception\OperationNotSupportedException;
use Kwaadpepper\LaravelStorageManager\Exception\StorageConnectionException;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathNormalizer;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\DiskStorageStats;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use League\Flysystem\FileAttributes;
use League\Flysystem\StorageAttributes;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;

readonly class LocalStorageStrategy implements StorageDriverStrategy
{
    public function __construct(
        private FilesystemFactory $filesystemFactory,
        private PathNormalizer $pathNormalizer,
        private LoggerInterface $logger,
    ) {
    }

    #[\Override]
    public function moveFile(Disk $disk, string $source, string $destination): true | FileOperationException
    {
        $this->logger->info(sprintf('Moving local file on disk "%s" from "%s" to "%s"', $disk->name, $source, $destination));

        try {
            $storage = $this->getAdapter($disk);
            $moved   = $storage->move($source, $destination);
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('Failed to move local file on disk "%s" from "%s" to "%s"', $disk->name, $source, $destination), [
                'exception' => $e,
            ]);

            return $this->toFileOperationException($e);
        }

        if (! $moved) {
            return new FileOperationException(FileOperationError::UNKNOWN_ERROR);
        }

        return true;
    }

    #[\Override]
    public function moveDirectory(Disk $disk, string $source, string $destination): true | FileOperationException
    {
        $this->logger->info(sprintf('Moving local directory on disk "%s" from "%s" to "%s"', $disk->name, $source, $destination));

        try {
            $storage = $this->getAdapter($disk);
            $moved   = $storage->move($source, $destination);
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('Failed to move local directory on disk "%s" from "%s" to "%s"', $disk->name, $source, $destination), [
                'exception' => $e,
            ]);

            return $this->toFileOperationException($e);
        }

        if (! $moved) {
            return new FileOperationException(FileOperationError::UNKNOWN_ERROR);
        }

        return true;
    }

    #[\Override]
    public function copyFile(Disk $disk, string $source, string $destination): true | FileOperationException
    {
        $this->logger->info(sprintf('Copying local file on disk "%s" from "%s" to "%s"', $disk->name, $source, $destination));

        try {
            $storage = $this->getAdapter($disk);
            $copied  = $storage->copy($source, $destination);
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('Failed to copy local file on disk "%s" from "%s" to "%s"', $disk->name, $source, $destination), [
                'exception' => $e,
            ]);

            return $this->toFileOperationException($e);
        }

        if (! $copied) {
            return new FileOperationException(FileOperationError::UNKNOWN_ERROR);
        }

        return true;
    }

    #[\Override]
    public function copyDirectory(Disk $disk, string $source, string $destination): true | FileOperationException
    {
        $this->logger->info(sprintf('Copying local directory on disk "%s" from "%s" to "%s"', $disk->name, $source, $destination));

        try {
            $storage = $this->getAdapter($disk);

            if ($storage->makeDirectory($destination) === false) {
                $this->logger->error(sprintf('Failed to create destination directory "%s" on disk "%s"', $destination, $disk->name));

                return new FileOperationException(FileOperationError::UNKNOWN_ERROR);
            }

            $sourcePath = new Path($this->pathNormalizer->normalizePath($source));
            $destPath   = new Path($this->pathNormalizer->normalizePath($destination));

            /** @var list<string> $directories */
            $directories = $storage->allDirectories($source);
            foreach ($directories as $dir) {
                $dirPath   = new Path($this->pathNormalizer->normalizePath($dir));
                $targetDir = $dirPath->rebase($sourcePath, $destPath)->toRelative();
                if ($storage->makeDirectory($targetDir) === false) {
                    $this->logger->error(sprintf('Failed to create subdirectory "%s" on disk "%s"', $targetDir, $disk->name));

                    return new FileOperationException(FileOperationError::UNKNOWN_ERROR);
                }
            }

            /** @var list<string> $files */
            $files = $storage->allFiles($source);
            foreach ($files as $file) {
                $filePath       = new Path($this->pathNormalizer->normalizePath($file));
                $targetFilePath = $filePath->rebase($sourcePath, $destPath)->toRelative();
                $copyResult     = $this->copyFile($disk, $file, $targetFilePath);
                if ($copyResult instanceof FileOperationException) {
                    return $copyResult;
                }
            }

            return true;
        } catch (\Throwable $e) {
            return $this->toFileOperationException($e);
        }
    }

    #[\Override]
    public function supportsDirectoryMetadata(Disk $disk): bool
    {
        return true;
    }

    #[\Override]
    public function supportsPublicUrl(Disk $disk): bool
    {
        return true;
    }

    #[\Override]
    public function get(Disk $disk, string $path): string | FileOperationException
    {
        try {
            $storage = $this->getAdapter($disk);
            $content = $storage->get($path);
            if (! is_string($content)) {
                return new FileOperationException(FileOperationError::FILE_NOT_FOUND);
            }

            return $content;
        } catch (\Throwable $e) {
            return $this->toFileOperationException($e);
        }
    }

    #[\Override]
    public function lastModified(Disk $disk, Path $path): int | FileOperationException
    {
        try {
            $storage = $this->getAdapter($disk);
            $relPath = $path->toRelative();

            return $storage->lastModified($relPath);
        } catch (\Throwable $e) {
            return $this->toFileOperationException($e);
        }
    }

    #[\Override]
    public function visibility(Disk $disk, Path $path): string | FileOperationException
    {
        try {
            $storage = $this->getAdapter($disk);
            $relPath = $path->toRelative();
            $v       = $storage->getVisibility($relPath);
            if (! is_string($v)) {
                return new FileOperationException(FileOperationError::FILE_NOT_FOUND);
            }

            return $v;
        } catch (\Throwable $e) {
            return $this->toFileOperationException($e);
        }
    }

    #[\Override]
    public function mimeType(Disk $disk, Path $path): string | FileOperationException
    {
        try {
            $storage = $this->getAdapter($disk);
            $relPath = $path->toRelative();

            if ($storage->directoryExists($relPath)) {
                return new OperationNotSupportedException(FileOperationError::OPERATION_NOT_SUPPORTED);
            }

            $m = $storage->mimeType($relPath);
            if (! is_string($m)) {
                return new OperationNotSupportedException(FileOperationError::OPERATION_NOT_SUPPORTED);
            }

            return $m;
        } catch (\Throwable $e) {
            if ($this->isMimeUndetectable($e)) {
                return new OperationNotSupportedException(FileOperationError::OPERATION_NOT_SUPPORTED, $e);
            }

            return $this->toFileOperationException($e);
        }
    }

    #[\Override]
    public function publicUrl(Disk $disk, Path $path): string | FileOperationException
    {
        try {
            $storage = $this->getAdapter($disk);

            return $storage->url($path->toRelative());
        } catch (\Throwable $e) {
            return $this->toFileOperationException($e);
        }
    }

    #[\Override]
    public function calculateDiskUsedBytes(Disk $disk): int | FileOperationException
    {
        try {
            $storage    = $this->getAdapter($disk);
            $totalBytes = 0;

            foreach ($storage->getDriver()->listContents('', true) as $item) {
                if ($item->isFile() && $item instanceof FileAttributes) {
                    $totalBytes += $item->fileSize() ?? 0;
                }
            }

            return $totalBytes;
        } catch (\Throwable $e) {
            return $this->toFileOperationException($e);
        }
    }

    #[\Override]
    public function getDiskStats(Disk $disk): DiskStorageStats | FileOperationException
    {
        try {
            $storage   = $this->getAdapter($disk);
            $localPath = $storage->path('');

            if (is_dir($localPath)) {
                $totalSpace = disk_total_space($localPath);
                $freeSpace  = disk_free_space($localPath);

                if ($totalSpace !== false && $freeSpace !== false && $totalSpace > 0) {
                    $totalInt   = (int) $totalSpace;
                    $freeInt    = (int) $freeSpace;
                    $usedInt    = max(0, $totalInt - $freeInt);
                    $percentage = round(($usedInt / $totalInt) * 100, 1);

                    return new DiskStorageStats(
                        mode: 'partition',
                        total: $totalInt,
                        free: $freeInt,
                        used: $usedInt,
                        percentage: $percentage,
                    );
                }
            }
        } catch (\Throwable $diskEx) {
            unset($diskEx);
        }

        $used = $this->calculateDiskUsedBytes($disk);
        if ($used instanceof FileOperationException) {
            return $used;
        }

        return new DiskStorageStats(
            mode: 'folder',
            total: null,
            free: null,
            used: $used,
            percentage: null,
        );
    }

    #[\Override]
    /**
     * @return list<StorageAttributes> | FileOperationException
     */
    public function listContents(Disk $disk, string $directory): array | FileOperationException
    {
        try {
            $storage = $this->getAdapter($disk);
            $relDir  = $directory === '' ? '' : ltrim($this->pathNormalizer->normalizePath($directory), '/');

            return array_values(iterator_to_array($storage->getDriver()->listContents($relDir, false)));
        } catch (\Throwable $e) {
            return $this->toFileOperationException($e);
        }
    }

    #[\Override]
    public function hasSubdirectories(Disk $disk, string $path): bool
    {
        try {
            $storage       = $this->getAdapter($disk);
            $normalized    = $this->pathNormalizer->normalizePath($path);
            $relPath       = $normalized === '/' ? '' : ltrim($normalized, '/');
            $fullLocalPath = $storage->path($relPath);

            if (! is_dir($fullLocalPath)) {
                return false;
            }

            $handle = opendir($fullLocalPath);
            if ($handle === false) {
                return false;
            }

            try {
                while (($entry = readdir($handle)) !== false) {
                    if ($entry === '.' || $entry === '..') {
                        continue;
                    }
                    if (is_dir($fullLocalPath . DIRECTORY_SEPARATOR . $entry)) {
                        return true;
                    }
                }
            } finally {
                closedir($handle);
            }

            return false;
        } catch (\Throwable) {
            return false;
        }
    }

    #[\Override]
    public function put(Disk $disk, string $path, string $contents): true | FileOperationException
    {
        try {
            $storage = $this->getAdapter($disk);
            $result  = (bool) $storage->put($path, $contents);

            if (! $result) {
                return new FileOperationException(FileOperationError::UNKNOWN_ERROR);
            }

            return true;
        } catch (\Throwable $e) {
            return $this->toFileOperationException($e);
        }
    }

    #[\Override]
    public function delete(Disk $disk, string $path): true | FileOperationException
    {
        try {
            $storage = $this->getAdapter($disk);
            $result  = (bool) $storage->delete($path);

            if (! $result) {
                return new FileOperationException(FileOperationError::UNKNOWN_ERROR);
            }

            return true;
        } catch (\Throwable $e) {
            return $this->toFileOperationException($e);
        }
    }

    #[\Override]
    public function deleteDirectory(Disk $disk, string $path): true | FileOperationException
    {
        try {
            $storage = $this->getAdapter($disk);
            $result  = (bool) $storage->deleteDirectory($path);

            if (! $result) {
                return new FileOperationException(FileOperationError::UNKNOWN_ERROR);
            }

            return true;
        } catch (\Throwable $e) {
            return $this->toFileOperationException($e);
        }
    }

    #[\Override]
    public function makeDirectory(Disk $disk, string $path): true | FileOperationException
    {
        try {
            $storage = $this->getAdapter($disk);
            $result  = (bool) $storage->makeDirectory($path);

            if (! $result) {
                return new FileOperationException(FileOperationError::UNKNOWN_ERROR);
            }

            return true;
        } catch (\Throwable $e) {
            return $this->toFileOperationException($e);
        }
    }

    #[\Override]
    public function exists(Disk $disk, Path $path): bool
    {
        $normalized = $this->pathNormalizer->normalizePath($path->value);

        if ($normalized === '/') {
            return true;
        }

        try {
            $storage = $this->getAdapter($disk);
            $relPath = ltrim($normalized, '/');

            return $storage->exists($relPath) || $this->isDirectory($disk, $path);
        } catch (\Throwable) {
            return false;
        }
    }

    #[\Override]
    public function isDirectory(Disk $disk, Path $path): bool
    {
        $normalized = $this->pathNormalizer->normalizePath($path->value);

        if ($normalized === '/') {
            return true;
        }

        try {
            $storage = $this->getAdapter($disk);
            $relPath = ltrim($normalized, '/');

            try {
                return $storage->directoryExists($relPath);
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to check directoryExists via FilesystemAdapter, falling back to parent scanning', [
                    'disk'      => $disk->name,
                    'path'      => $path->value,
                    'exception' => $e,
                ]);
            }

            $parentDir = dirname($normalized);
            $parentRel = $parentDir === '/' || $parentDir === '.' ? '' : ltrim($parentDir, '/');

            /** @var list<string> $rawDirectories */
            $rawDirectories = $storage->directories($parentRel);

            /** @var list<string> $directories */
            $directories = array_map(
                fn (string $dir): string => $this->pathNormalizer->normalizePath($dir),
                $rawDirectories
            );

            return in_array($normalized, $directories, true);
        } catch (\Throwable) {
            return false;
        }
    }

    #[\Override]
    public function isFile(Disk $disk, Path $path): bool
    {
        if ($path->isRoot()) {
            return false;
        }

        try {
            $storage = $this->getAdapter($disk);

            return $storage->exists($path->toRelative()) && ! $this->isDirectory($disk, $path);
        } catch (\Throwable) {
            return false;
        }
    }

    #[\Override]
    public function size(Disk $disk, string $path): int | FileOperationException
    {
        try {
            $storage = $this->getAdapter($disk);

            return (int) $storage->size($path);
        } catch (\Throwable $e) {
            return $this->toFileOperationException($e);
        }
    }

    #[\Override]
    public function files(Disk $disk, string $directory = ''): array | FileOperationException
    {
        try {
            $storage = $this->getAdapter($disk);
            /** @var list<string> $files */
            $files = $storage->files($directory);

            return $files;
        } catch (\Throwable $e) {
            return $this->toFileOperationException($e);
        }
    }

    #[\Override]
    public function directories(Disk $disk, string $directory = ''): array | FileOperationException
    {
        try {
            $storage = $this->getAdapter($disk);
            /** @var list<string> $dirs */
            $dirs = $storage->directories($directory);

            return $dirs;
        } catch (\Throwable $e) {
            return $this->toFileOperationException($e);
        }
    }

    #[\Override]
    public function allFiles(Disk $disk, string $directory = ''): array | FileOperationException
    {
        try {
            $storage = $this->getAdapter($disk);
            /** @var list<string> $allFiles */
            $allFiles = $storage->allFiles($directory);

            return $allFiles;
        } catch (\Throwable $e) {
            return $this->toFileOperationException($e);
        }
    }

    #[\Override]
    public function allDirectories(Disk $disk, string $directory = ''): array | FileOperationException
    {
        try {
            $storage = $this->getAdapter($disk);
            /** @var list<string> $allDirs */
            $allDirs = $storage->allDirectories($directory);

            return $allDirs;
        } catch (\Throwable $e) {
            return $this->toFileOperationException($e);
        }
    }

    #[\Override]
    public function readStream(Disk $disk, Path $path): mixed
    {
        try {
            $storage = $this->getAdapter($disk);
            $stream  = $storage->readStream($path->toRelative());

            if (! is_resource($stream)) {
                return new FileOperationException(FileOperationError::UNKNOWN_ERROR);
            }

            return $stream;
        } catch (\Throwable $e) {
            return $this->toFileOperationException($e);
        }
    }

    #[\Override]
    public function writeStream(Disk $disk, Path $path, mixed $resource): true | FileOperationException
    {
        try {
            $storage = $this->getAdapter($disk);
            $result  = $storage->writeStream($path->toRelative(), $resource);

            if (! $result) {
                return new FileOperationException(FileOperationError::UNKNOWN_ERROR);
            }

            return true;
        } catch (\Throwable $e) {
            return $this->toFileOperationException($e);
        }
    }

    #[\Override]
    public function localPath(Disk $disk, string $path = ''): ?string
    {
        try {
            $storage = $this->getAdapter($disk);

            return $storage->path($path);
        } catch (\Throwable) {
            return null;
        }
    }

    private function getAdapter(Disk $disk): FilesystemAdapter
    {
        $storage = $this->filesystemFactory->disk($disk->name);
        Assert::isInstanceOf($storage, FilesystemAdapter::class);

        return $storage;
    }

    private function isPermissionDenied(\Throwable $e): bool
    {
        $curr = $e;
        while ($curr instanceof \Throwable) {
            if (method_exists($curr, 'getStatusCode')) {
                $status = $curr->getStatusCode();
                if (is_int($status) && $status === 403) {
                    return true;
                }
            }

            $className = $curr::class;
            if (str_ends_with($className, 'UnableToAuthenticate')) {
                return true;
            }

            $msg = strtolower($curr->getMessage());
            if (
                str_contains($msg, 'permission denied')
                || str_contains($msg, 'access denied')
                || str_contains($msg, 'accessdenied')
                || str_contains($msg, 'forbidden')
            ) {
                return true;
            }

            $curr = $curr->getPrevious();
        }

        return false;
    }

    private function isConnectionError(\Throwable $e): bool
    {
        $curr = $e;
        while ($curr instanceof \Throwable) {
            $className = $curr::class;
            if (
                str_ends_with($className, 'UnableToConnectToFtpHost')
                || str_ends_with($className, 'UnableToConnectToSftpHost')
                || str_ends_with($className, 'ConnectException')
                || str_ends_with($className, 'CredentialsException')
            ) {
                return true;
            }

            if (method_exists($curr, 'getStatusCode')) {
                $status = $curr->getStatusCode();
                if (is_int($status) && in_array($status, [0, 500, 502, 503, 504], true)) {
                    return true;
                }
            }

            $msg = strtolower($curr->getMessage());
            if (
                str_contains($msg, 'connection refused')
                || str_contains($msg, 'connection reset')
                || str_contains($msg, 'timed out')
                || str_contains($msg, 'timeout')
                || str_contains($msg, 'network is unreachable')
                || str_contains($msg, 'could not resolve host')
                || str_contains($msg, 'failed to connect')
                || str_contains($msg, 'socket closed')
            ) {
                return true;
            }

            $curr = $curr->getPrevious();
        }

        return false;
    }

    private function isNotFound(\Throwable $e): bool
    {
        $curr = $e;
        while ($curr instanceof \Throwable) {
            if (method_exists($curr, 'getStatusCode')) {
                $status = $curr->getStatusCode();
                if (is_int($status) && $status === 404) {
                    return true;
                }
            }

            $msg = strtolower($curr->getMessage());
            if (
                str_contains($msg, 'not found')
                || str_contains($msg, 'does not exist')
                || str_contains($msg, 'no such file')
                || str_contains($msg, 'nosuchkey')
            ) {
                return true;
            }

            $curr = $curr->getPrevious();
        }

        return false;
    }

    private function isMimeUndetectable(\Throwable $e): bool
    {
        $curr = $e;
        while ($curr instanceof \Throwable) {
            $className = $curr::class;
            if (str_ends_with($className, 'UnableToRetrieveMetadata') || str_ends_with($className, 'UnableToDetectMimeType')) {
                return true;
            }

            $msg = strtolower($curr->getMessage());
            if (str_contains($msg, 'mime') || str_contains($msg, 'mimetype')) {
                return true;
            }

            $curr = $curr->getPrevious();
        }

        return false;
    }

    private function toFileOperationException(\Throwable $e): FileOperationException
    {
        if ($this->isNotFound($e)) {
            return new FileOperationException(FileOperationError::FILE_NOT_FOUND, $e);
        }

        if ($this->isPermissionDenied($e)) {
            return new FileOperationException(FileOperationError::PERMISSION_DENIED, $e);
        }

        if ($this->isConnectionError($e)) {
            return new StorageConnectionException(FileOperationError::CONNECTION_ERROR, $e);
        }

        return new FileOperationException(FileOperationError::IO_ERROR, $e);
    }
}
