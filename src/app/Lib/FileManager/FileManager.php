<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\FileManager;

use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;
use Kwaadpepper\LaravelStorageManager\Event\BulkRenamed;
use Kwaadpepper\LaravelStorageManager\Event\DirectoryCopied;
use Kwaadpepper\LaravelStorageManager\Event\DirectoryCreated;
use Kwaadpepper\LaravelStorageManager\Event\DirectoryDeleted;
use Kwaadpepper\LaravelStorageManager\Event\DirectoryMoved;
use Kwaadpepper\LaravelStorageManager\Event\DirectoryRenamed;
use Kwaadpepper\LaravelStorageManager\Event\FileCopied;
use Kwaadpepper\LaravelStorageManager\Event\FileCreated;
use Kwaadpepper\LaravelStorageManager\Event\FileDeleted;
use Kwaadpepper\LaravelStorageManager\Event\FileManagerShowed;
use Kwaadpepper\LaravelStorageManager\Event\FileMoved;
use Kwaadpepper\LaravelStorageManager\Event\FileRenamed;
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

readonly class FileManager
{
    private const int MIME_SAMPLE_BYTES = 1048576; // 1 MB

    public function __construct(
        private PathNormalizer $pathNormalizer,
        private FilePropertyExtractor $filePropertyExtractor,
        private FileMetadataExtractor $fileMetadataExtractor,
        private StorageManagerConfig $config,
        private StorageGateway $storageGateway,
        private MimeTypeChecker $mimeTypeChecker,
        private TrashService $trashService,
        private EventDispatcher $eventDispatcher,
    ) {
    }

    /**
     * @return array{chunkMinSize: int, chunkMaxSize: int}
     */
    public function init(): array
    {
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
        $directoriesToScan  = [];
        $matchedDirectories = [];
        $matchedFiles       = [];

        foreach ($paths as $pathObj) {
            $directory = $this->pathNormalizer->normalizePath($pathObj->value);

            if ($this->isTrashPath($directory)) {
                continue;
            }

            $entries = $this->listCleanDirectoryEntries($disk, $directory);

            foreach ($entries['directories'] as $dir) {
                $path                = new Path($dir);
                $directoriesToScan[] = $path;

                $properties = $this->filePropertyExtractor->directoryProperties($disk, $path);

                if ($properties instanceof DirectoryPathProperties) {
                    if ($this->isDirectoryMatchingFilters($path, $query, $extension, $minSize, $maxSize)) {
                        $matchedDirectories[] = new PathTreeDirectory(
                            $path,
                            $this->hasSubdirectories((string) $path, $disk),
                            $properties->visibility
                        );
                    }
                }
            }

            foreach ($entries['files'] as $file) {
                $path       = new Path($file);
                $properties = $this->filePropertyExtractor->fileProperties($disk, $path);

                if (! ($properties instanceof FilePathProperties)) {
                    continue;
                }

                if (! $this->isFileMatchingFilters($path, $properties, $query, $extension, $minSize, $maxSize)) {
                    continue;
                }

                $publicUrl = $this->storageGateway->publicUrl($disk, $path);

                $matchedFiles[] = new PathTreeFile(
                    $path,
                    $properties->size,
                    $properties->extension,
                    $properties->visibility,
                    $publicUrl
                );
            }
        }

        return new SearchResult($directoriesToScan, $matchedDirectories, $matchedFiles);
    }

    private function isDirectoryMatchingFilters(
        Path $path,
        ?string $query,
        ?string $extension,
        ?int $minSize,
        ?int $maxSize,
    ): bool {
        if ($extension !== null || $minSize !== null || $maxSize !== null) {
            return false;
        }

        if ($query !== null) {
            $dirname = basename($path->value);
            if (stripos($dirname, $query) === false) {
                return false;
            }
        }

        return true;
    }

    private function isFileMatchingFilters(
        Path $path,
        FilePathProperties $properties,
        ?string $query,
        ?string $extension,
        ?int $minSize,
        ?int $maxSize,
    ): bool {
        if ($extension !== null && strtolower($properties->extension) !== strtolower($extension)) {
            return false;
        }

        if ($minSize !== null && $properties->size < $minSize) {
            return false;
        }

        if ($maxSize !== null && $properties->size > $maxSize) {
            return false;
        }

        if ($query !== null) {
            $filename = basename($path->value);
            if (stripos($filename, $query) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * @throws FileOperationException
     */
    public function createDirectory(Disk $disk, Path $path): void
    {
        $disk->assertWritable();

        $normalizedPath = $this->pathNormalizer->normalizePath($path->value);

        if ($this->storageGateway->exists($disk, $path)) {
            FileOperationException::throwWith(FileOperationError::DIRECTORY_ALREADY_EXISTS);
        }

        if ($this->storageGateway->makeDirectory($disk, $normalizedPath) === false) {
            FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
        }

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
            FileOperationException::throwWith(FileOperationError::INVALID_PATH);
        }

        if (! $this->storageGateway->isDirectory($disk, $path)) {
            FileOperationException::throwWith(FileOperationError::DIRECTORY_NOT_FOUND);
        }

        if (! $force) {
            $files       = $this->storageGateway->files($disk, $normalizedPath);
            $directories = $this->storageGateway->directories($disk, $normalizedPath);
            if (count($files) > 0 || count($directories) > 0) {
                FileOperationException::throwWith(FileOperationError::DIRECTORY_NOT_EMPTY);
            }
        }

        if ($this->storageGateway->deleteDirectory($disk, $normalizedPath) === false) {
            FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
        }

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
            FileOperationException::throwWith(FileOperationError::FORBIDDEN_FILE_EXTENSION);
        }

        if ($content !== '') {
            $detectedMime = $this->mimeTypeChecker->detectBufferMimeType($content);
            if (! $this->mimeTypeChecker->isMimeAllowedForExtension($extension, $detectedMime)) {
                FileOperationException::throwWith(FileOperationError::FORBIDDEN_FILE_MIME);
            }
        }

        if ($this->storageGateway->exists($disk, $path)) {
            FileOperationException::throwWith(FileOperationError::FILE_ALREADY_EXISTS);
        }

        $this->storageGateway->put($disk, $normalizedPath, $content);

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
            FileOperationException::throwWith(FileOperationError::FILE_NOT_FOUND);
        }

        if ($this->storageGateway->delete($disk, $normalizedPath) === false) {
            FileOperationException::throwWith(FileOperationError::UNKNOWN_ERROR);
        }

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
        $disk->assertWritable();

        $normalizedSource = $this->pathNormalizer->normalizePath($source->value);
        $sourceIsDir      = $this->storageGateway->resolveIsDirectory($disk, $source);

        $destination = Path::appendTo(
            new Path(dirname($normalizedSource)),
            $this->pathNormalizer->normalizePath($newName)
        );

        if (! $sourceIsDir) {
            $this->validateFileRenameTarget($disk, $source, $destination);
        }

        if ($this->storageGateway->exists($disk, $destination)) {
            FileOperationException::throwWith(
                $sourceIsDir
                    ? FileOperationError::DIRECTORY_ALREADY_EXISTS
                    : FileOperationError::FILE_ALREADY_EXISTS
            );
        }

        $this->storageGateway->move($disk, $normalizedSource, $destination->value, $sourceIsDir);

        $this->eventDispatcher->dispatch(
            $sourceIsDir ? DirectoryRenamed::class : FileRenamed::class,
            [
                'old_path' => $source->value,
                'new_path' => $destination->value,
            ]
        );

        return $destination;
    }

    /**
     * @throws FileOperationException
     */
    public function copy(Disk $disk, Path $source, Path $destinationDir): Path
    {
        $disk->assertWritable();

        $normalizedSource = $this->pathNormalizer->normalizePath($source->value);
        $sourceIsDir      = $this->storageGateway->resolveIsDirectory($disk, $source);

        $basename    = basename($normalizedSource);
        $desired     = Path::appendTo($destinationDir, $this->pathNormalizer->normalizePath($basename));
        $destination = $this->storageGateway->resolveAvailablePath($disk, $desired, '%s (copy%s)%s');

        $this->storageGateway->copy($disk, $source, $destination, $sourceIsDir);

        $this->eventDispatcher->dispatch(
            $sourceIsDir ? DirectoryCopied::class : FileCopied::class,
            [
                'source_path' => $source->value,
                'target_path' => $destination->value,
            ]
        );

        return $destination;
    }

    /**
     * @throws FileOperationException
     */
    public function move(Disk $disk, Path $source, Path $destinationDir): void
    {
        $disk->assertWritable();

        $normalizedSource = $this->pathNormalizer->normalizePath($source->value);
        $sourceIsDir      = $this->storageGateway->resolveIsDirectory($disk, $source);

        $basename    = basename($normalizedSource);
        $desired     = Path::appendTo($destinationDir, $this->pathNormalizer->normalizePath($basename));
        $destination = $this->storageGateway->resolveAvailablePath($disk, $desired, '%s (copy%s)%s');

        $this->storageGateway->move($disk, $normalizedSource, $destination->value, $sourceIsDir);

        $this->eventDispatcher->dispatch(
            $sourceIsDir ? DirectoryMoved::class : FileMoved::class,
            [
                'source_path' => $source->value,
                'target_path' => $destinationDir->value,
            ]
        );
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
     * @throws FileOperationException
     */
    private function validateFileRenameTarget(Disk $disk, Path $source, Path $destination): void
    {
        $oldExt = strtolower(pathinfo($source->value, PATHINFO_EXTENSION));
        $newExt = strtolower(pathinfo($destination->value, PATHINFO_EXTENSION));

        if (! $this->config->upload->isExtensionAllowed($newExt)) {
            FileOperationException::throwWith(FileOperationError::FORBIDDEN_FILE_EXTENSION);
        }

        if ($oldExt === $newExt) {
            return;
        }

        $sample = $this->storageGateway->readStream($disk, $source)->getContents(self::MIME_SAMPLE_BYTES);

        if ($sample !== '') {
            $detectedMime = $this->mimeTypeChecker->detectBufferMimeType($sample);
            if (! $this->mimeTypeChecker->isMimeAllowedForExtension($newExt, $detectedMime)) {
                FileOperationException::throwWith(FileOperationError::FORBIDDEN_FILE_MIME);
            }
        }
    }

    /**
     * @param  array<int, array{from: Path, to: string}>  $renames
     * @return array<int, array{from: string, to: string}>
     *
     * @throws FileOperationException
     */
    public function bulkRename(Disk $disk, array $renames): array
    {
        $disk->assertWritable();

        $plan = $this->buildBulkRenamePlan($disk, $renames);

        $sourcePaths = array_map(
            fn (array $p): string => $this->pathNormalizer->normalizePath($p['from']->value),
            $plan
        );
        $hasOverlap = false;
        foreach ($plan as $p) {
            $normTarget = $this->pathNormalizer->normalizePath($p['target']->value);
            if (in_array($normTarget, $sourcePaths, true)) {
                $hasOverlap = true;
                break;
            }
        }

        $result = $this->executeBulkRename($disk, $plan, $hasOverlap);

        $this->eventDispatcher->dispatch(
            BulkRenamed::class,
            ['renamed' => $result]
        );

        return $result;
    }

    /**
     * @param  array<int, array{from: Path, to: string}>  $renames
     * @return array<int, array{from: Path, toName: string, target: Path, isDir: bool}>
     *
     * @throws FileOperationException
     */
    private function buildBulkRenamePlan(Disk $disk, array $renames): array
    {
        $plan = [];

        foreach ($renames as $rename) {
            $from           = $rename['from'];
            $toName         = $rename['to'];
            $normalizedFrom = $this->pathNormalizer->normalizePath($from->value);
            $isDir          = $this->storageGateway->resolveIsDirectory($disk, $from);

            $target = Path::appendTo(
                new Path(dirname($normalizedFrom)),
                $this->pathNormalizer->normalizePath($toName)
            );

            if (! $isDir) {
                $this->validateFileRenameTarget($disk, $from, $target);
            }

            $plan[] = [
                'from'   => $from,
                'toName' => $toName,
                'target' => $target,
                'isDir'  => $isDir,
            ];
        }

        $targetPaths = array_map(fn (array $p): string => (string) $p['target'], $plan);
        if (count($targetPaths) !== count(array_unique($targetPaths))) {
            FileOperationException::throwWith(FileOperationError::FILE_ALREADY_EXISTS);
        }

        $sourcePaths = array_map(
            fn (array $p): string => $this->pathNormalizer->normalizePath($p['from']->value),
            $plan
        );
        foreach ($plan as $p) {
            $normTarget = $this->pathNormalizer->normalizePath($p['target']->value);
            if (! in_array($normTarget, $sourcePaths, true) && $this->storageGateway->exists($disk, $p['target'])) {
                FileOperationException::throwWith(
                    $p['isDir']
                        ? FileOperationError::DIRECTORY_ALREADY_EXISTS
                        : FileOperationError::FILE_ALREADY_EXISTS
                );
            }
        }

        return $plan;
    }

    /**
     * @param  array<int, array{from: Path, toName: string, target: Path, isDir: bool}>  $plan
     * @return array<int, array{from: string, to: string}>
     */
    private function executeBulkRename(Disk $disk, array $plan, bool $hasOverlap): array
    {
        $result = [];

        if ($hasOverlap) {
            $tempPlan = [];
            foreach ($plan as $idx => $p) {
                $tempName   = sprintf('.tmp_bulk_%s_%d', uniqid(), $idx);
                $tempTarget = Path::appendTo(
                    new Path(dirname($this->pathNormalizer->normalizePath($p['from']->value))),
                    $tempName
                );
                $this->storageGateway->move(
                    $disk,
                    $p['from']->value,
                    $tempTarget->value,
                    $p['isDir']
                );
                $tempPlan[] = [
                    'temp'   => $tempTarget,
                    'target' => $p['target'],
                    'from'   => $p['from'],
                    'isDir'  => $p['isDir'],
                ];
            }

            foreach ($tempPlan as $tp) {
                $this->storageGateway->move(
                    $disk,
                    $tp['temp']->value,
                    $tp['target']->value,
                    $tp['isDir']
                );
                $this->eventDispatcher->dispatch(
                    $tp['isDir'] ? DirectoryRenamed::class : FileRenamed::class,
                    [
                        'old_path' => (string) $tp['from'],
                        'new_path' => (string) $tp['target'],
                    ]
                );
                $result[] = [
                    'from' => (string) $tp['from'],
                    'to'   => (string) $tp['target'],
                ];
            }
        } else {
            foreach ($plan as $p) {
                $this->rename($disk, $p['from'], $p['toName']);
                $result[] = [
                    'from' => (string) $p['from'],
                    'to'   => (string) $p['target'],
                ];
            }
        }

        return $result;
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
