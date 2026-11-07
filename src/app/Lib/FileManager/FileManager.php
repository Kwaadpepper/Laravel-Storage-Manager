<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\FileManager;

use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;
use Kwaadpepper\LaravelStorageManager\Event\DirectoryCreated;
use Kwaadpepper\LaravelStorageManager\Event\DirectoryDeleted;
use Kwaadpepper\LaravelStorageManager\Event\FileCreated;
use Kwaadpepper\LaravelStorageManager\Event\FileDeleted;
use Kwaadpepper\LaravelStorageManager\Event\FileManagerShowed;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Lib\Event\EventDispatcher;
use Kwaadpepper\LaravelStorageManager\Lib\Security\MimeTypeChecker;
use Kwaadpepper\LaravelStorageManager\Lib\StorageGateway;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\FileStream;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\DirectoryPathProperties;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\FilePathProperties;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathList as PathContent;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathProperties;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Trash\TrashedItem;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeDirectory;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeFile;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeLevel;
use Kwaadpepper\LaravelStorageManager\Service\TrashService;
use Psr\Log\LoggerInterface;

readonly class FileManager
{
    public function __construct(
        private PathNormalizer $pathNormalizer,
        private FilePropertyExtractor $filePropertyExtractor,
        private FileMetadataExtractor $fileMetadataExtractor,
        private StorageManagerConfig $config,
        private StorageGateway $storageGateway,
        private MimeTypeChecker $mimeTypeChecker,
        private TrashService $trashService,
        private EventDispatcher $eventDispatcher,
        private FileSearchService $fileSearchService,
        private FileTransferService $fileTransferService,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return array{chunkMinSize: int, chunkMaxSize: int}
     */
    public function init(): array
    {
        $this->logger->info('Initializing file manager');
        $this->eventDispatcher->dispatch(FileManagerShowed::class);

        return [
            'chunkMinSize' => $this->config->upload->chunkMinSize,
            'chunkMaxSize' => $this->config->upload->chunkMaxSize,
        ];
    }

    /**
     * @throws FileOperationException
     */
    public function getProperties(Disk $disk, Path $path): PathProperties
    {
        $this->logger->debug('Getting properties for path', ['disk' => $disk->name, 'path' => $path->value]);
        $normalizedPath    = $this->pathNormalizer->normalizePath($path->value);
        $sourceIsDir       = $this->storageGateway->resolveIsDirectory($disk, $path);
        $normalizedPathObj = new Path($normalizedPath);

        return $sourceIsDir
            ? $this->filePropertyExtractor->directoryProperties($disk, $normalizedPathObj)
            : $this->fileMetadataExtractor->extract($disk, $normalizedPathObj);
    }

    public function getContent(Disk $disk, ?Path $path = null): PathContent
    {
        $directory = $this->pathNormalizer->normalizePath($path ? $path->value : '/');
        $this->logger->debug('Getting directory content', ['disk' => $disk->name, 'directory' => $directory]);

        if ($this->isTrashPath($directory)) {
            return new PathContent([], []);
        }

        $entries = $this->listCleanDirectoryEntries($disk, $directory);

        $files       = array_map(fn (string $file): Path => new Path($file), $entries['files']);
        $directories = array_map(fn (string $dir): Path => new Path($dir), $entries['directories']);

        return new PathContent(
            array_values($files),
            array_values($directories),
        );
    }

    public function getPathTree(Disk $disk, ?Path $path = null): PathTreeLevel
    {
        $directory = $this->pathNormalizer->normalizePath($path ? $path->value : '/');
        $this->logger->debug('Getting path tree', ['disk' => $disk->name, 'directory' => $directory]);

        if ($this->isTrashPath($directory)) {
            return new PathTreeLevel([], []);
        }

        $entries = $this->listCleanDirectoryEntries($disk, $directory);

        $directories = array_map(function (string $dir) use ($disk): PathTreeDirectory {
            $dirPath    = new Path($dir);
            $properties = $this->filePropertyExtractor->directoryProperties($disk, $dirPath);

            if (! ($properties instanceof DirectoryPathProperties)) {
                throw new \LogicException("Expected directory properties for path '{$dirPath}', got file properties.");
            }

            return new PathTreeDirectory(
                $dirPath,
                $this->hasSubdirectories((string) $dirPath, $disk),
                $properties->visibility
            );
        }, $entries['directories']);

        $files = array_map(function (string $file) use ($disk): PathTreeFile {
            $filePath   = new Path($file);
            $properties = $this->filePropertyExtractor->fileProperties($disk, $filePath);

            if (! ($properties instanceof FilePathProperties)) {
                throw new \LogicException("Expected file properties for path '{$filePath}', got directory properties.");
            }

            try {
                $publicUrl = $this->storageGateway->publicUrl($disk, $filePath);
            } catch (\Throwable) {
                $publicUrl = null;
            }

            return new PathTreeFile(
                $filePath,
                $properties->size,
                $properties->extension,
                $properties->visibility,
                $publicUrl
            );
        }, $entries['files']);

        return new PathTreeLevel(
            array_values($directories),
            array_values($files)
        );
    }

    /**
     * @param  Path[]  $paths
     */
    public function searchContent(
        Disk $disk,
        array $paths,
        ?string $query,
        ?string $extension,
        ?int $minSize,
        ?int $maxSize,
    ): SearchResult {
        $this->logger->debug('Delegating searchContent to FileSearchService', [
            'disk'      => $disk->name,
            'paths'     => array_map(fn (Path $p): string => $p->value, $paths),
            'query'     => $query,
            'extension' => $extension,
            'minSize'   => $minSize,
            'maxSize'   => $maxSize,
        ]);

        return $this->fileSearchService->search($disk, $paths, $query, $extension, $minSize, $maxSize);
    }

    /**
     * @throws FileOperationException
     */
    public function createDirectory(Disk $disk, Path $path): void
    {
        $disk->assertWritable();

        $normalizedPath = $this->pathNormalizer->normalizePath($path->value);

        if ($this->storageGateway->exists($disk, $path)) {
            $this->logger->warning('Rejected createDirectory: directory already exists', ['disk' => $disk->name, 'path' => $path->value]);
            FileOperationException::throwWith(FileOperationError::DIRECTORY_ALREADY_EXISTS);
        }

        if ($this->storageGateway->makeDirectory($disk, $normalizedPath) === false) {
            $this->logger->error('Failed to create directory', ['disk' => $disk->name, 'path' => $path->value]);
            FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
        }

        $this->logger->info('Directory created successfully', ['disk' => $disk->name, 'path' => $path->value]);
        $this->eventDispatcher->dispatch(
            DirectoryCreated::class,
            ['path' => $path->value]
        );
    }

    /**
     * @throws FileOperationException
     */
    public function delete(Disk $disk, Path $path, bool $force = false): ?TrashedItem
    {
        $disk->assertWritable();
        $this->logger->info('Deleting item', ['disk' => $disk->name, 'path' => $path->value, 'force' => $force]);

        if ($this->config->trash->enabled && ! $force) {
            return $this->trashService->moveToTrash($disk, $path);
        }

        if ($this->storageGateway->resolveIsDirectory($disk, $path)) {
            $this->deleteDirectory($disk, $path, $force);

            return null;
        }

        $this->deleteFile($disk, $path);

        return null;
    }

    /**
     * @throws FileOperationException
     */
    public function deleteDirectory(Disk $disk, Path $path, bool $force = false): void
    {
        $disk->assertWritable();

        $normalizedPath = $this->pathNormalizer->normalizePath($path->value);

        if ($this->pathNormalizer->isRootPath($normalizedPath)) {
            $this->logger->warning('Rejected deleteDirectory: cannot delete root path', ['disk' => $disk->name, 'path' => $path->value]);
            FileOperationException::throwWith(FileOperationError::INVALID_PATH);
        }

        if (! $this->storageGateway->isDirectory($disk, $path)) {
            $this->logger->warning('Rejected deleteDirectory: directory not found', ['disk' => $disk->name, 'path' => $path->value]);
            FileOperationException::throwWith(FileOperationError::DIRECTORY_NOT_FOUND);
        }

        if (! $force) {
            $files       = $this->storageGateway->files($disk, $normalizedPath);
            $directories = $this->storageGateway->directories($disk, $normalizedPath);
            if (count($files) > 0 || count($directories) > 0) {
                $this->logger->warning('Rejected deleteDirectory: directory not empty', ['disk' => $disk->name, 'path' => $path->value]);
                FileOperationException::throwWith(FileOperationError::DIRECTORY_NOT_EMPTY);
            }
        }

        if ($this->storageGateway->deleteDirectory($disk, $normalizedPath) === false) {
            $this->logger->error('Failed to delete directory', ['disk' => $disk->name, 'path' => $path->value]);
            FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
        }

        $this->logger->info('Directory deleted successfully', ['disk' => $disk->name, 'path' => $path->value]);
        $this->eventDispatcher->dispatch(
            DirectoryDeleted::class,
            ['path' => $path->value]
        );
    }

    /**
     * @throws FileOperationException
     */
    public function createFile(Disk $disk, Path $path, string $content = ''): void
    {
        $disk->assertWritable();

        $normalizedPath = $this->pathNormalizer->normalizePath($path->value);
        $extension      = strtolower(pathinfo($normalizedPath, PATHINFO_EXTENSION));

        if (! $this->config->upload->isExtensionAllowed($extension)) {
            $this->logger->warning('Rejected createFile: forbidden extension', [
                'disk'      => $disk->name,
                'path'      => $path->value,
                'extension' => $extension,
            ]);
            FileOperationException::throwWith(FileOperationError::FORBIDDEN_FILE_EXTENSION);
        }

        if ($content !== '') {
            $detectedMime = $this->mimeTypeChecker->detectBufferMimeType($content);
            if (! $this->mimeTypeChecker->isMimeAllowedForExtension($extension, $detectedMime)) {
                $this->logger->warning('Rejected createFile: forbidden mime type for extension', [
                    'disk'      => $disk->name,
                    'path'      => $path->value,
                    'extension' => $extension,
                    'mime'      => $detectedMime,
                ]);
                FileOperationException::throwWith(FileOperationError::FORBIDDEN_FILE_MIME);
            }
        }

        if ($this->storageGateway->exists($disk, $path)) {
            $this->logger->warning('Rejected createFile: file already exists', ['disk' => $disk->name, 'path' => $path->value]);
            FileOperationException::throwWith(FileOperationError::FILE_ALREADY_EXISTS);
        }

        if ($this->storageGateway->put($disk, $normalizedPath, $content) === false) {
            $this->logger->error('Failed to create file', ['disk' => $disk->name, 'path' => $path->value]);
            FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
        }

        $this->logger->info('File created successfully', ['disk' => $disk->name, 'path' => $path->value]);
        $this->eventDispatcher->dispatch(
            FileCreated::class,
            ['path' => $path->value]
        );
    }

    /**
     * @throws FileOperationException
     */
    public function deleteFile(Disk $disk, Path $path): void
    {
        $disk->assertWritable();

        $normalizedPath = $this->pathNormalizer->normalizePath($path->value);

        if (! $this->storageGateway->isFile($disk, $path)) {
            $this->logger->warning('Rejected deleteFile: file not found', ['disk' => $disk->name, 'path' => $path->value]);
            FileOperationException::throwWith(FileOperationError::FILE_NOT_FOUND);
        }

        if ($this->storageGateway->delete($disk, $normalizedPath) === false) {
            $this->logger->error('Failed to delete file', ['disk' => $disk->name, 'path' => $path->value]);
            FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
        }

        $this->logger->info('File deleted successfully', ['disk' => $disk->name, 'path' => $path->value]);
        $this->eventDispatcher->dispatch(
            FileDeleted::class,
            ['path' => $path->value]
        );
    }

    /**
     * @throws FileOperationException
     */
    public function rename(Disk $disk, Path $source, string $newName): Path
    {
        $this->logger->debug('Delegating rename to FileTransferService', [
            'disk'    => $disk->name,
            'source'  => $source->value,
            'newName' => $newName,
        ]);

        return $this->fileTransferService->rename($disk, $source, $newName);
    }

    /**
     * @throws FileOperationException
     */
    public function copy(Disk $disk, Path $source, Path $destinationDir): Path
    {
        $this->logger->debug('Delegating copy to FileTransferService', [
            'disk'           => $disk->name,
            'source'         => $source->value,
            'destinationDir' => $destinationDir->value,
        ]);

        return $this->fileTransferService->copy($disk, $source, $destinationDir);
    }

    /**
     * @throws FileOperationException
     */
    public function move(Disk $disk, Path $source, Path $destinationDir): Path
    {
        $this->logger->debug('Delegating move to FileTransferService', [
            'disk'           => $disk->name,
            'source'         => $source->value,
            'destinationDir' => $destinationDir->value,
        ]);

        return $this->fileTransferService->move($disk, $source, $destinationDir);
    }

    public function exists(Disk $disk, Path $path): bool
    {
        return $this->storageGateway->exists($disk, $path);
    }

    private function isTrashPath(string $normalizedPath): bool
    {
        $trashFolder = '/' . trim($this->config->trash->folder, '/');

        return $normalizedPath === $trashFolder || str_starts_with($normalizedPath, $trashFolder . '/');
    }

    private function hasSubdirectories(string $path, Disk $disk): bool
    {
        $directories = $this->storageGateway->directories($disk, $path);

        return ! empty(array_filter(
            $directories,
            fn (string $dir): bool => ! $this->isTrashPath($this->pathNormalizer->normalizePath($dir))
        ));
    }

    public function isDirectory(Disk $disk, Path $path): bool
    {
        return $this->storageGateway->isDirectory($disk, $path);
    }

    public function isFile(Disk $disk, Path $path): bool
    {
        return $this->storageGateway->isFile($disk, $path);
    }

    /**
     * @throws FileOperationException
     */
    public function readStream(Disk $disk, Path $path): FileStream
    {
        return $this->storageGateway->readStream($disk, $path);
    }

    /**
     * @param  array<int, array{from: Path, to: string}>  $renames
     * @return array<int, array{from: string, to: string}>
     *
     * @throws FileOperationException
     */
    public function bulkRename(Disk $disk, array $renames): array
    {
        $this->logger->debug('Delegating bulkRename to FileTransferService', [
            'disk'  => $disk->name,
            'count' => count($renames),
        ]);

        return $this->fileTransferService->bulkRename($disk, $renames);
    }

    /**
     * @return array{directories: list<string>, files: list<string>}
     */
    private function listCleanDirectoryEntries(Disk $disk, string $directory): array
    {
        $rawDirectories = $this->storageGateway->directories($disk, $directory);
        $rawFiles       = $this->storageGateway->files($disk, $directory);

        $directories = array_map(
            fn (string $dir): string => $this->pathNormalizer->normalizePath($dir),
            $rawDirectories
        );

        $files = array_map(
            fn (string $file): string => $this->pathNormalizer->normalizePath($file),
            $rawFiles
        );

        $directories = array_values(array_filter($directories, fn (string $dir): bool => ! $this->isTrashPath($dir)));
        $files       = array_values(array_filter($files, fn (string $file): bool => ! $this->isTrashPath($file)));

        return [
            'directories' => $directories,
            'files'       => $files,
        ];
    }
}
