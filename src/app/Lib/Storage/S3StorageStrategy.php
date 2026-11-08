<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\Storage;

use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Filesystem\AwsS3V3Adapter;
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
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;

readonly class S3StorageStrategy implements StorageDriverStrategy
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
        $this->logger->info(sprintf('Moving S3 object on disk "%s" from "%s" to "%s"', $disk->name, $source, $destination));

        $copyResult = $this->copyFile($disk, $source, $destination);
        if ($copyResult instanceof FileOperationException) {
            return $copyResult;
        }

        try {
            return $this->getAdapter($disk)->delete($source)
                ? true
                : new FileOperationException(FileOperationError::UNKNOWN_ERROR);
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('Failed to delete source S3 object "%s" on disk "%s" after copy', $source, $disk->name), [
                'exception' => $e,
            ]);

            return $this->toFileOperationException($e);
        }
    }

    #[\Override]
    public function moveDirectory(Disk $disk, string $source, string $destination): true | FileOperationException
    {
        $this->logger->info(sprintf('Moving S3 directory prefix on disk "%s" from "%s" to "%s"', $disk->name, $source, $destination));

        $copyResult = $this->copyDirectory($disk, $source, $destination);
        if ($copyResult instanceof FileOperationException) {
            return $copyResult;
        }

        try {
            if ($this->getAdapter($disk)->deleteDirectory($source) === false) {
                $this->logger->error(sprintf('Failed to delete source S3 directory prefix "%s" on disk "%s"', $source, $disk->name));

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
        $this->logger->info(sprintf('Copying S3 object on disk "%s" from "%s" to "%s"', $disk->name, $source, $destination));

        $storage = $this->getAdapter($disk);

        if ($storage instanceof AwsS3V3Adapter) {
            return $this->serverSideCopy($storage, $source, $destination);
        }

        try {
            return $storage->copy($source, $destination)
                ? true
                : new FileOperationException(FileOperationError::UNKNOWN_ERROR);
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('Failed to copy object on S3 disk "%s" from "%s" to "%s"', $disk->name, $source, $destination), [
                'exception' => $e,
            ]);

            return $this->toFileOperationException($e);
        }
    }

    #[\Override]
    public function copyDirectory(Disk $disk, string $source, string $destination): true | FileOperationException
    {
        $this->logger->info(sprintf('Copying S3 directory prefix on disk "%s" from "%s" to "%s"', $disk->name, $source, $destination));

        try {
            $storage = $this->getAdapter($disk);

            if ($storage->makeDirectory($destination) === false) {
                $this->logger->error(sprintf('Failed to create S3 directory prefix "%s" on disk "%s"', $destination, $disk->name));

                return new FileOperationException(FileOperationError::UNKNOWN_ERROR);
            }

            /** @var list<string> $directories */
            $directories = $storage->allDirectories($source);
            foreach ($directories as $dir) {
                $targetDir = $this->resolveTargetSubPath($dir, $source, $destination);
                if ($storage->makeDirectory($targetDir) === false) {
                    $this->logger->error(sprintf('Failed to create S3 subdirectory prefix "%s" on disk "%s"', $targetDir, $disk->name));

                    return new FileOperationException(FileOperationError::UNKNOWN_ERROR);
                }
            }

            /** @var list<string> $files */
            $files = $storage->allFiles($source);
            foreach ($files as $file) {
                $copyResult = $this->copyFile($disk, $file, $this->resolveTargetSubPath($file, $source, $destination));
                if ($copyResult instanceof FileOperationException) {
                    return $copyResult;
                }
            }

            return true;
        } catch (\Throwable $e) {
            return $this->toFileOperationException($e);
        }
    }

    private function resolveTargetSubPath(string $itemPath, string $source, string $destination): string
    {
        $normalized   = $this->pathNormalizer->normalizePath($itemPath);
        $relativePath = substr($normalized, strlen('/' . $source) + 1);

        return ltrim($destination . '/' . $relativePath, '/');
    }

    private function serverSideCopy(AwsS3V3Adapter $storage, string $source, string $destination): true | FileOperationException
    {
        /** @var array<string, mixed> $config */
        $config     = $storage->getConfig();
        $bucket     = is_string($config['bucket'] ?? null) ? $config['bucket'] : '';
        $srcKey     = $storage->path($source);
        $destKey    = $storage->path($destination);
        $encodedKey = implode('/', array_map(rawurlencode(...), explode('/', ltrim($srcKey, '/'))));

        $lastException = null;
        foreach (["/{$bucket}/{$encodedKey}", "{$bucket}/{$encodedKey}"] as $copySource) {
            try {
                $storage->getClient()->copyObject([
                    'Bucket'     => $bucket,
                    'Key'        => $destKey,
                    'CopySource' => $copySource,
                ]);

                return true;
            } catch (\Throwable $e) {
                $lastException = $e;
            }
        }

        $this->logger->error(sprintf('S3 server-side copy failed from "%s" to "%s"', $source, $destination), [
            'exception' => $lastException,
        ]);

        return $this->toFileOperationException($lastException);
    }

    #[\Override]
    public function supportsDirectoryMetadata(Disk $disk): bool
    {
        return false;
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
            $content = $this->getAdapter($disk)->get($path);

            return is_string($content)
                ? $content
                : new FileOperationException(FileOperationError::FILE_NOT_FOUND);
        } catch (\Throwable $e) {
            return $this->toFileOperationException($e);
        }
    }

    #[\Override]
    public function lastModified(Disk $disk, Path $path): int | FileOperationException
    {
        return $this->forFileOnly(
            $disk,
            $path,
            static fn (FilesystemAdapter $storage, string $relPath) => $storage->lastModified($relPath),
        );
    }

    #[\Override]
    public function visibility(Disk $disk, Path $path): string | FileOperationException
    {
        return $this->forFileOnly($disk, $path, static function (FilesystemAdapter $storage, string $relPath) {
            $v = $storage->getVisibility($relPath);

            return is_string($v) ? $v : new FileOperationException(FileOperationError::FILE_NOT_FOUND);
        });
    }

    #[\Override]
    public function mimeType(Disk $disk, Path $path): string | FileOperationException
    {
        return $this->forFileOnly(
            $disk,
            $path,
            static function (FilesystemAdapter $storage, string $relPath) {
                $m = $storage->mimeType($relPath);

                return is_string($m) ? $m : new OperationNotSupportedException(FileOperationError::OPERATION_NOT_SUPPORTED);
            },
            fn (\Throwable $e) => $this->isMimeUndetectable($e)
                ? new OperationNotSupportedException(FileOperationError::OPERATION_NOT_SUPPORTED, $e)
                : $this->toFileOperationException($e),
        );
    }

    /**
     * @template T of int|string
     *
     * @param  \Closure(FilesystemAdapter, string): (T|FileOperationException)  $callback
     * @param  (\Closure(\Throwable): FileOperationException)|null  $errorMapper
     * @return T|FileOperationException
     */
    private function forFileOnly(
        Disk $disk,
        Path $path,
        \Closure $callback,
        ?\Closure $errorMapper = null,
    ): int | string | FileOperationException {
        try {
            $storage = $this->getAdapter($disk);
            $relPath = $path->toRelative();

            if ($storage->directoryExists($relPath)) {
                return new OperationNotSupportedException(FileOperationError::OPERATION_NOT_SUPPORTED);
            }

            return $callback($storage, $relPath);
        } catch (\Throwable $e) {
            return $errorMapper !== null ? $errorMapper($e) : $this->toFileOperationException($e);
        }
    }

    #[\Override]
    public function publicUrl(Disk $disk, Path $path): string | FileOperationException
    {
        try {
            return $this->getAdapter($disk)->url($path->toRelative());
        } catch (\Throwable $e) {
            return $this->toFileOperationException($e);
        }
    }

    #[\Override]
    public function calculateDiskUsedBytes(Disk $disk): int | FileOperationException
    {
        try {
            $totalBytes = 0;

            foreach ($this->getAdapter($disk)->getDriver()->listContents('', true) as $item) {
                if ($item instanceof FileAttributes && $item->isFile()) {
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
        return new DiskStorageStats(mode: 'unlimited');
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
                str_ends_with($className, 'ConnectException')
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
