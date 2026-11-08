<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\Storage;

use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Config;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Exception\OperationNotSupportedException;
use Kwaadpepper\LaravelStorageManager\Exception\StorageConnectionException;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathNormalizer;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\DiskStorageStats;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use League\Flysystem\FileAttributes;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;

readonly class RemoteStorageStrategy implements StorageDriverStrategy
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
        $this->logger->info(sprintf('Moving remote file on disk "%s" (%s) from "%s" to "%s"', $disk->name, $disk->driver, $source, $destination));

        try {
            $storage = $this->getAdapter($disk);
            $moved   = $storage->move($source, $destination);
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('Failed to move remote file on disk "%s" from "%s" to "%s"', $disk->name, $source, $destination), [
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
        $this->logger->info(sprintf(
            'Moving remote directory on disk "%s" (%s) from "%s" to "%s"',
            $disk->name,
            $disk->driver,
            $source,
            $destination
        ));

        try {
            $storage = $this->getAdapter($disk);

            if ($storage->makeDirectory($destination) === false) {
                $this->logger->error(sprintf('Failed to create remote directory "%s" on disk "%s"', $destination, $disk->name));

                return new FileOperationException(FileOperationError::UNKNOWN_ERROR);
            }

            /** @var list<string> $directories */
            $directories = $storage->allDirectories($source);
            foreach ($directories as $dir) {
                $normalizedDir = $this->pathNormalizer->normalizePath($dir);
                $relativePath  = substr($normalizedDir, strlen('/' . $source) + 1);
                $targetDir     = ltrim($destination . '/' . $relativePath, '/');
                if ($storage->makeDirectory($targetDir) === false) {
                    $this->logger->error(sprintf('Failed to create remote subdirectory "%s" on disk "%s"', $targetDir, $disk->name));

                    return new FileOperationException(FileOperationError::UNKNOWN_ERROR);
                }
            }

            /** @var list<string> $files */
            $files = $storage->allFiles($source);
            foreach ($files as $file) {
                $normalizedFile = $this->pathNormalizer->normalizePath($file);
                $relativePath   = substr($normalizedFile, strlen('/' . $source) + 1);
                $targetFile     = ltrim($destination . '/' . $relativePath, '/');
                $moveResult     = $this->moveFile($disk, $file, $targetFile);
                if ($moveResult instanceof FileOperationException) {
                    return $moveResult;
                }
            }

            if ($storage->deleteDirectory($source) === false) {
                $this->logger->error(sprintf('Failed to delete remote source directory "%s" on disk "%s"', $source, $disk->name));

                return new FileOperationException(FileOperationError::UNKNOWN_ERROR);
            }

            return true;
        } catch (\Throwable $e) {
            return $this->toFileOperationException($e);
        }
    }

    #[\Override]
    public function copyFile(Disk $disk, string $source, string $destination): true | FileOperationException
    {
        $this->logger->info(sprintf('Copying remote file on disk "%s" (%s) from "%s" to "%s"', $disk->name, $disk->driver, $source, $destination));

        try {
            $storage = $this->getAdapter($disk);
            $result  = $this->streamCopy($storage, $disk, $source, $destination);
            if ($result instanceof FileOperationException) {
                return $result;
            }

            if (! $result) {
                return new FileOperationException(FileOperationError::UNKNOWN_ERROR);
            }

            return true;
        } catch (\Throwable $e) {
            return $this->toFileOperationException($e);
        }
    }

    #[\Override]
    public function copyDirectory(Disk $disk, string $source, string $destination): true | FileOperationException
    {
        $this->logger->info(sprintf(
            'Copying remote directory on disk "%s" (%s) from "%s" to "%s"',
            $disk->name,
            $disk->driver,
            $source,
            $destination
        ));

        try {
            $storage = $this->getAdapter($disk);

            if ($storage->makeDirectory($destination) === false) {
                $this->logger->error(sprintf('Failed to create remote destination directory "%s" on disk "%s"', $destination, $disk->name));

                return new FileOperationException(FileOperationError::UNKNOWN_ERROR);
            }

            /** @var list<string> $directories */
            $directories = $storage->allDirectories($source);
            foreach ($directories as $dir) {
                $normalizedDir = $this->pathNormalizer->normalizePath($dir);
                $relativePath  = substr($normalizedDir, strlen('/' . $source) + 1);
                $targetDir     = ltrim($destination . '/' . $relativePath, '/');
                if ($storage->makeDirectory($targetDir) === false) {
                    $this->logger->error(sprintf('Failed to create remote subdirectory "%s" on disk "%s"', $targetDir, $disk->name));

                    return new FileOperationException(FileOperationError::UNKNOWN_ERROR);
                }
            }

            /** @var list<string> $files */
            $files = $storage->allFiles($source);
            foreach ($files as $file) {
                $normalizedFile = $this->pathNormalizer->normalizePath($file);
                $relativePath   = substr($normalizedFile, strlen('/' . $source) + 1);
                $targetFilePath = ltrim($destination . '/' . $relativePath, '/');
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

    private function streamCopy(Filesystem $storage, Disk $disk, string $source, string $destination): bool | FileOperationException
    {
        try {
            $stream = $storage->readStream($source);
            if ($stream === false || $stream === null) {
                $this->logger->error(sprintf('Failed to open read stream for "%s" on remote disk "%s"', $source, $disk->name));

                return new FileOperationException(FileOperationError::UNKNOWN_ERROR);
            }

            $written = $storage->writeStream($destination, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }

            return $written !== false;
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('Failed to stream copy on remote disk "%s" from "%s" to "%s"', $disk->name, $source, $destination), [
                'exception' => $e,
            ]);

            return $this->toFileOperationException($e);
        }
    }

    #[\Override]
    public function supportsDirectoryMetadata(Disk $disk): bool
    {
        return false;
    }

    #[\Override]
    public function supportsPublicUrl(Disk $disk): bool
    {
        $url = Config::get("filesystems.disks.{$disk->name}.url");

        return is_string($url) && $url !== '';
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

            if ($storage->directoryExists($relPath)) {
                return new OperationNotSupportedException(FileOperationError::OPERATION_NOT_SUPPORTED);
            }

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

            if ($storage->directoryExists($relPath)) {
                return new OperationNotSupportedException(FileOperationError::OPERATION_NOT_SUPPORTED);
            }

            $v = $storage->getVisibility($relPath);
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
        if (! $this->supportsPublicUrl($disk)) {
            return new OperationNotSupportedException(FileOperationError::OPERATION_NOT_SUPPORTED);
        }

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
        return new DiskStorageStats(
            mode: 'unlimited',
        );
    }

    #[\Override]
    /**
     * @return list<\League\Flysystem\StorageAttributes> | FileOperationException
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
        return true;
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
