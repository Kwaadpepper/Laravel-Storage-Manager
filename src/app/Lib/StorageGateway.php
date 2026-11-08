<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib;

use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Exception\OperationNotSupportedException;
use Kwaadpepper\LaravelStorageManager\Exception\StorageConnectionException;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathNormalizer;
use Kwaadpepper\LaravelStorageManager\Lib\Storage\LocalStorageStrategy;
use Kwaadpepper\LaravelStorageManager\Lib\Storage\RemoteStorageStrategy;
use Kwaadpepper\LaravelStorageManager\Lib\Storage\S3StorageStrategy;
use Kwaadpepper\LaravelStorageManager\Lib\Storage\StorageDriverStrategy;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\DiskStorageStats;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\FileStream;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\FilePathProperties;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathPropertyFactory;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\ResolvedFile;
use League\Flysystem\StorageAttributes;
use Psr\Log\LoggerInterface;

readonly class StorageGateway
{
    public function __construct(
        private FilesystemFactory $filesystemFactory,
        private PathNormalizer $pathNormalizer,
        private LocalStorageStrategy $localStorageStrategy,
        private S3StorageStrategy $s3StorageStrategy,
        private RemoteStorageStrategy $remoteStorageStrategy,
        private LoggerInterface $logger,
    ) {
    }

    public function disk(Disk $disk): Filesystem
    {
        return $this->filesystemFactory->disk($disk->name);
    }

    /**
     * @throws FileOperationException
     * @throws StorageConnectionException
     */
    public function get(Disk $disk, string $path): ?string
    {
        $relPath = ltrim($this->pathNormalizer->normalizePath($path), '/');
        $this->logger->debug('Reading file contents from disk', ['disk' => $disk->name, 'path' => $relPath]);

        $result = $this->strategyFor($disk)->get($disk, $relPath);

        if ($result instanceof FileOperationException) {
            if ($result->getDomainErrorCode() === FileOperationError::FILE_NOT_FOUND) {
                return null;
            }

            $this->logger->error('Failed to read file from disk', [
                'disk'      => $disk->name,
                'path'      => $relPath,
                'exception' => $result,
            ]);

            throw $result;
        }

        return $result;
    }

    /**
     * @throws FileOperationException
     */
    public function put(Disk $disk, string $path, string $contents): bool
    {
        $disk->assertWritable();
        $relPath = ltrim($this->pathNormalizer->normalizePath($path), '/');

        $result = $this->strategyFor($disk)->put($disk, $relPath, $contents);
        if ($result instanceof FileOperationException) {
            $this->logger->error('Failed to write file to disk', [
                'disk'      => $disk->name,
                'path'      => $relPath,
                'exception' => $result,
            ]);

            throw $result;
        }

        $this->logger->info('File written successfully', ['disk' => $disk->name, 'path' => $relPath]);

        return true;
    }

    /**
     * @throws FileOperationException
     */
    public function delete(Disk $disk, string $path): bool
    {
        $disk->assertWritable();
        $relPath = ltrim($this->pathNormalizer->normalizePath($path), '/');

        $result = $this->strategyFor($disk)->delete($disk, $relPath);
        if ($result instanceof FileOperationException) {
            $this->logger->error('Failed to delete file from disk', [
                'disk'      => $disk->name,
                'path'      => $relPath,
                'exception' => $result,
            ]);

            throw $result;
        }

        $this->logger->info('File deleted successfully', ['disk' => $disk->name, 'path' => $relPath]);

        return true;
    }

    /**
     * @throws FileOperationException
     */
    public function deleteDirectory(Disk $disk, string $path): bool
    {
        $disk->assertWritable();
        $relPath = ltrim($this->pathNormalizer->normalizePath($path), '/');

        $result = $this->strategyFor($disk)->deleteDirectory($disk, $relPath);
        if ($result instanceof FileOperationException) {
            $this->logger->error('Failed to delete directory from disk', [
                'disk'      => $disk->name,
                'path'      => $relPath,
                'exception' => $result,
            ]);

            throw $result;
        }

        $this->logger->info('Directory deleted successfully', ['disk' => $disk->name, 'path' => $relPath]);

        return true;
    }

    /**
     * @throws FileOperationException
     */
    public function makeDirectory(Disk $disk, string $path): bool
    {
        $disk->assertWritable();
        $relPath = ltrim($this->pathNormalizer->normalizePath($path), '/');

        $result = $this->strategyFor($disk)->makeDirectory($disk, $relPath);
        if ($result instanceof FileOperationException) {
            $this->logger->error('Failed to create directory on disk', [
                'disk'      => $disk->name,
                'path'      => $relPath,
                'exception' => $result,
            ]);

            throw $result;
        }

        $this->logger->info('Directory created successfully', ['disk' => $disk->name, 'path' => $relPath]);

        return true;
    }

    /**
     * @return list<string>
     *
     * @throws FileOperationException
     */
    public function files(Disk $disk, string $directory = ''): array
    {
        $relDir = $directory === '' ? '' : ltrim($this->pathNormalizer->normalizePath($directory), '/');
        $this->logger->debug('Listing files in directory', ['disk' => $disk->name, 'directory' => $relDir]);

        $result = $this->strategyFor($disk)->files($disk, $relDir);
        if ($result instanceof FileOperationException) {
            $this->logger->error('Failed to list files in directory', [
                'disk'      => $disk->name,
                'directory' => $relDir,
                'exception' => $result,
            ]);

            throw $result;
        }

        return $result;
    }

    /**
     * @return list<string>
     *
     * @throws FileOperationException
     */
    public function directories(Disk $disk, string $directory = ''): array
    {
        $relDir = $directory === '' ? '' : ltrim($this->pathNormalizer->normalizePath($directory), '/');
        $this->logger->debug('Listing directories', ['disk' => $disk->name, 'directory' => $relDir]);

        $result = $this->strategyFor($disk)->directories($disk, $relDir);
        if ($result instanceof FileOperationException) {
            $this->logger->error('Failed to list directories', [
                'disk'      => $disk->name,
                'directory' => $relDir,
                'exception' => $result,
            ]);

            throw $result;
        }

        return $result;
    }

    /**
     * @return list<StorageAttributes>
     *
     * @throws FileOperationException
     * @throws StorageConnectionException
     */
    public function listContents(Disk $disk, string $directory = ''): array
    {
        $relDir = $directory === '' ? '' : ltrim($this->pathNormalizer->normalizePath($directory), '/');
        $this->logger->debug('Listing contents with attributes', ['disk' => $disk->name, 'directory' => $relDir]);

        $result = $this->strategyFor($disk)->listContents($disk, $relDir);

        if ($result instanceof FileOperationException) {
            if ($result->getDomainErrorCode() === FileOperationError::FILE_NOT_FOUND) {
                return [];
            }

            $this->logger->error('Failed to list directory contents', [
                'disk'      => $disk->name,
                'directory' => $relDir,
                'exception' => $result,
            ]);

            throw $result;
        }

        return $result;
    }

    public function hasSubdirectories(Disk $disk, Path $path): bool
    {
        return $this->strategyFor($disk)->hasSubdirectories($disk, $path->value);
    }

    /**
     * @throws FileOperationException
     * @throws StorageConnectionException
     */
    public function publicUrl(Disk $disk, Path $path): ?string
    {
        $this->logger->debug('Getting public URL for path', ['disk' => $disk->name, 'path' => $path->value]);

        $result = $this->strategyFor($disk)->publicUrl($disk, $path);

        if ($result instanceof OperationNotSupportedException) {
            return null;
        }

        if ($result instanceof FileOperationException) {
            $this->logger->error('Failed to get public URL for path', [
                'disk'      => $disk->name,
                'path'      => $path->value,
                'exception' => $result,
            ]);

            throw $result;
        }

        return $result;
    }

    public function localPath(Disk $disk, string $path = ''): ?string
    {
        $relPath = $path === '' ? '' : ltrim($this->pathNormalizer->normalizePath($path), '/');

        return $this->strategyFor($disk)->localPath($disk, $relPath);
    }

    /**
     * @throws FileOperationException
     */
    public function size(Disk $disk, string $path): int
    {
        $relPath = ltrim($this->pathNormalizer->normalizePath($path), '/');
        $this->logger->debug('Getting size for path', ['disk' => $disk->name, 'path' => $relPath]);

        $result = $this->strategyFor($disk)->size($disk, $relPath);

        if ($result instanceof FileOperationException) {
            $this->logger->error('Failed to get size for path', [
                'disk'      => $disk->name,
                'path'      => $relPath,
                'exception' => $result,
            ]);

            throw $result;
        }

        return $result;
    }

    /**
     * @throws FileOperationException
     * @throws StorageConnectionException
     */
    public function lastModified(Disk $disk, Path $path): ?int
    {
        $this->logger->debug('Getting last modified for path', ['disk' => $disk->name, 'path' => $path->value]);

        $result = $this->strategyFor($disk)->lastModified($disk, $path);

        if ($result instanceof OperationNotSupportedException) {
            return null;
        }

        if ($result instanceof FileOperationException) {
            if ($result->getDomainErrorCode() === FileOperationError::FILE_NOT_FOUND) {
                return null;
            }

            $this->logger->error('Failed to get last modified timestamp', [
                'disk'      => $disk->name,
                'path'      => $path->value,
                'exception' => $result,
            ]);

            throw $result;
        }

        return $result;
    }

    /**
     * @throws FileOperationException
     * @throws StorageConnectionException
     */
    public function visibility(Disk $disk, Path $path): ?string
    {
        $this->logger->debug('Getting visibility for path', ['disk' => $disk->name, 'path' => $path->value]);

        $result = $this->strategyFor($disk)->visibility($disk, $path);

        if ($result instanceof OperationNotSupportedException) {
            return null;
        }

        if ($result instanceof FileOperationException) {
            if ($result->getDomainErrorCode() === FileOperationError::FILE_NOT_FOUND) {
                return null;
            }

            $this->logger->error('Failed to get visibility', [
                'disk'      => $disk->name,
                'path'      => $path->value,
                'exception' => $result,
            ]);

            throw $result;
        }

        return $result;
    }

    /**
     * @throws FileOperationException
     * @throws StorageConnectionException
     */
    public function mimeType(Disk $disk, Path $path): ?string
    {
        $this->logger->debug('Getting mime type for path', ['disk' => $disk->name, 'path' => $path->value]);

        $result = $this->strategyFor($disk)->mimeType($disk, $path);

        if ($result instanceof OperationNotSupportedException) {
            return null;
        }

        if ($result instanceof FileOperationException) {
            if ($result->getDomainErrorCode() === FileOperationError::FILE_NOT_FOUND) {
                return null;
            }

            $this->logger->error('Failed to get mime type', [
                'disk'      => $disk->name,
                'path'      => $path->value,
                'exception' => $result,
            ]);

            throw $result;
        }

        return $result;
    }

    /**
     * @return list<string>
     *
     * @throws FileOperationException
     */
    public function allFiles(Disk $disk, string $directory = ''): array
    {
        $relDir = $directory === '' ? '' : ltrim($this->pathNormalizer->normalizePath($directory), '/');
        $this->logger->debug('Listing all files recursively', ['disk' => $disk->name, 'directory' => $relDir]);

        $result = $this->strategyFor($disk)->allFiles($disk, $relDir);
        if ($result instanceof FileOperationException) {
            $this->logger->error('Failed to list all files', [
                'disk'      => $disk->name,
                'directory' => $relDir,
                'exception' => $result,
            ]);

            throw $result;
        }

        return $result;
    }

    /**
     * @return list<string>
     *
     * @throws FileOperationException
     */
    public function allDirectories(Disk $disk, string $directory = ''): array
    {
        $relDir = $directory === '' ? '' : ltrim($this->pathNormalizer->normalizePath($directory), '/');
        $this->logger->debug('Listing all directories recursively', ['disk' => $disk->name, 'directory' => $relDir]);

        $result = $this->strategyFor($disk)->allDirectories($disk, $relDir);
        if ($result instanceof FileOperationException) {
            $this->logger->error('Failed to list all directories', [
                'disk'      => $disk->name,
                'directory' => $relDir,
                'exception' => $result,
            ]);

            throw $result;
        }

        return $result;
    }

    public function exists(Disk $disk, Path $path): bool
    {
        return $this->strategyFor($disk)->exists($disk, $path);
    }

    public function isDirectory(Disk $disk, Path $path): bool
    {
        return $this->strategyFor($disk)->isDirectory($disk, $path);
    }

    public function isFile(Disk $disk, Path $path): bool
    {
        return $this->strategyFor($disk)->isFile($disk, $path);
    }

    /**
     * Assert that the path exists on the disk and return whether it is a directory.
     *
     * @throws FileOperationException
     */
    public function resolveIsDirectory(Disk $disk, Path $path): bool
    {
        if ($this->isDirectory($disk, $path)) {
            return true;
        }

        if ($this->isFile($disk, $path)) {
            return false;
        }

        FileOperationException::throwWith(FileOperationError::FILE_NOT_FOUND);
    }

    /**
     * @throws FileOperationException
     */
    public function readStream(Disk $disk, Path $path, bool $skipExistenceCheck = false): FileStream
    {
        $this->logger->debug('Opening read stream', ['disk' => $disk->name, 'path' => $path->value]);

        if (! $skipExistenceCheck) {
            if (! $this->isFile($disk, $path)) {
                if ($this->isDirectory($disk, $path)) {
                    FileOperationException::throwWith(FileOperationError::INVALID_PATH);
                }

                FileOperationException::throwWith(FileOperationError::FILE_NOT_FOUND);
            }
        }

        $result = $this->strategyFor($disk)->readStream($disk, $path);
        if ($result instanceof FileOperationException) {
            $this->logger->error('Failed to open read stream', [
                'disk'      => $disk->name,
                'path'      => $path->value,
                'exception' => $result,
            ]);

            throw $result;
        }

        return new FileStream($result);
    }

    /**
     * @throws FileOperationException
     */
    public function writeStream(Disk $disk, Path $path, FileStream $stream): void
    {
        $disk->assertWritable();
        $this->logger->debug('Writing stream to disk', ['disk' => $disk->name, 'path' => $path->value]);

        try {
            $result = $this->strategyFor($disk)->writeStream($disk, $path, $stream->stream());
            if ($result instanceof FileOperationException) {
                $this->logger->error('Failed to write stream to disk', [
                    'disk'      => $disk->name,
                    'path'      => $path->value,
                    'exception' => $result,
                ]);

                throw $result;
            }

            $this->logger->info('Stream written successfully', ['disk' => $disk->name, 'path' => $path->value]);
        } finally {
            $this->logger->debug('Closing stream after write', ['disk' => $disk->name, 'path' => $path->value]);
            $stream->close();
        }
    }

    /**
     * @param  resource  $resource
     *
     * @throws FileOperationException
     */
    public function putStream(Disk $disk, Path $path, mixed $resource): void
    {
        $disk->assertWritable();
        $this->logger->debug('Writing raw stream to disk', ['disk' => $disk->name, 'path' => $path->value]);

        $result = $this->strategyFor($disk)->writeStream($disk, $path, $resource);
        if ($result instanceof FileOperationException) {
            $this->logger->error('Failed to write raw stream to disk', [
                'disk'      => $disk->name,
                'path'      => $path->value,
                'exception' => $result,
            ]);

            throw $result;
        }

        $this->logger->info('Raw stream written successfully', ['disk' => $disk->name, 'path' => $path->value]);
    }

    /**
     * @throws FileOperationException
     */
    public function move(Disk $disk, string $source, string $destination, bool $isDirectory): void
    {
        $disk->assertWritable();

        $srcRel  = ltrim($this->pathNormalizer->normalizePath($source), '/');
        $destRel = ltrim($this->pathNormalizer->normalizePath($destination), '/');

        if ($srcRel === $destRel) {
            return;
        }

        $strategy = $this->strategyFor($disk);

        $result = $isDirectory
            ? $strategy->moveDirectory($disk, $srcRel, $destRel)
            : $strategy->moveFile($disk, $srcRel, $destRel);

        if ($result instanceof FileOperationException) {
            $this->logger->error('Failed to move path', [
                'disk'        => $disk->name,
                'source'      => $srcRel,
                'destination' => $destRel,
                'exception'   => $result,
            ]);

            throw $result;
        }

        $this->logger->info($isDirectory ? 'Directory moved successfully' : 'File moved successfully', [
            'disk'        => $disk->name,
            'source'      => $srcRel,
            'destination' => $destRel,
        ]);
    }

    /**
     * @throws FileOperationException
     */
    public function copy(Disk $disk, Path $source, Path $destination, bool $isDirectory): void
    {
        $disk->assertWritable();

        $srcRel  = $source->toRelative();
        $destRel = $destination->toRelative();

        if ($srcRel === $destRel) {
            return;
        }

        $strategy = $this->strategyFor($disk);

        $result = $isDirectory
            ? $strategy->copyDirectory($disk, $srcRel, $destRel)
            : $strategy->copyFile($disk, $srcRel, $destRel);

        if ($result instanceof FileOperationException) {
            $this->logger->error('Failed to copy path', [
                'disk'        => $disk->name,
                'source'      => $srcRel,
                'destination' => $destRel,
                'exception'   => $result,
            ]);

            throw $result;
        }

        $this->logger->info($isDirectory ? 'Directory copied successfully' : 'File copied successfully', [
            'disk'        => $disk->name,
            'source'      => $srcRel,
            'destination' => $destRel,
        ]);
    }

    /**
     * @throws FileOperationException
     */
    public function resolveExistingFile(Disk $disk, string $rawPath): ResolvedFile
    {
        $path = new Path($this->pathNormalizer->normalizePath(rawurldecode($rawPath)));

        if (! $this->isFile($disk, $path)) {
            FileOperationException::throwWith(FileOperationError::FILE_NOT_FOUND);
        }

        /** @var array{dirname?: string, basename?: string, extension?: string, filename?: string} $pathInfo */
        $pathInfo   = pathinfo($path->value);
        $rawDirname = $pathInfo['dirname'] ?? '.';
        $dirname    = $rawDirname === '.' ? '' : $rawDirname;
        $basename   = $pathInfo['basename']  ?? '';
        $extension  = $pathInfo['extension'] ?? '';
        $filename   = $pathInfo['filename']  ?? '';

        $properties = PathPropertyFactory::fromArray([
            'type'       => 'file',
            'path'       => $path,
            'basename'   => $basename,
            'dirname'    => $dirname,
            'extension'  => $extension,
            'filename'   => $filename,
            'size'       => $this->size($disk, $path->value),
            'timestamp'  => $this->lastModified($disk, $path),
            'visibility' => $this->visibility($disk, $path),
        ]);

        if (! ($properties instanceof FilePathProperties)) {
            FileOperationException::throwWith(FileOperationError::FILE_NOT_FOUND);
        }

        return new ResolvedFile($disk, $path, $properties);
    }

    public function supportsDirectoryMetadata(Disk $disk): bool
    {
        return $this->strategyFor($disk)->supportsDirectoryMetadata($disk);
    }

    public function supportsPublicUrl(Disk $disk): bool
    {
        return $this->strategyFor($disk)->supportsPublicUrl($disk);
    }

    /**
     * @throws FileOperationException
     * @throws StorageConnectionException
     */
    public function calculateDiskUsedBytes(Disk $disk): int
    {
        $result = $this->strategyFor($disk)->calculateDiskUsedBytes($disk);

        if ($result instanceof FileOperationException) {
            $this->logger->error('Failed to calculate disk used bytes', [
                'disk'      => $disk->name,
                'exception' => $result,
            ]);

            throw $result;
        }

        return $result;
    }

    /**
     * @throws FileOperationException
     */
    public function getDiskStats(Disk $disk): DiskStorageStats
    {
        $result = $this->strategyFor($disk)->getDiskStats($disk);

        if ($result instanceof FileOperationException) {
            $this->logger->error('Failed to get disk stats', [
                'disk'      => $disk->name,
                'exception' => $result,
            ]);

            throw $result;
        }

        return $result;
    }

    private function strategyFor(Disk $disk): StorageDriverStrategy
    {
        return match ($disk->driver) {
            'local'       => $this->localStorageStrategy,
            's3'          => $this->s3StorageStrategy,
            'ftp', 'sftp' => $this->remoteStorageStrategy,
            default       => $this->localStorageStrategy,
        };
    }
}
