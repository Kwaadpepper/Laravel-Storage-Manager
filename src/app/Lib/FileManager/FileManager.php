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
            fn (string $file): string => $this->pathNormalizer->normalizePath($file),
            $filesystem->files($directory)
        );
        $directories = array_map(
            fn (string $dir): string => $this->pathNormalizer->normalizePath($dir),
            $filesystem->directories($directory)
        );

        $files       = array_map(fn (string $file): Path => new Path($file), $files);
        $directories = array_map(fn (string $dir): Path => new Path($dir), $directories);

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
            fn (string $dir): string => $this->pathNormalizer->normalizePath($dir),
            $filesystem->directories($directory)
        );

        $files = array_map(
            fn (string $file): string => $this->pathNormalizer->normalizePath($file),
            $filesystem->files($directory)
        );

        $directories = array_map(function (string $dir) use ($filesystem): PathTreeDirectory {
            $path       = new Path($dir);
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

        $files = array_map(function (string $file) use ($filesystem): PathTreeFile {
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
     * @param  Path[]  $paths
     */
    /**
     * @param  Path[]  $paths
     */
    public function searchContent(array $paths, ?string $query, ?string $extension, ?int $minSize, ?int $maxSize): SearchResult
    {
        $filesystem         = $this->getStorage();
        $directoriesToScan  = [];
        $matchedDirectories = [];
        $matchedFiles       = [];

        foreach ($paths as $pathObj) {
            $directory = $this->pathNormalizer->normalizePath($pathObj->value);

            $directories = array_map(
                fn (string $dir): string => $this->pathNormalizer->normalizePath($dir),
                $filesystem->directories($directory)
            );

            $files = array_map(
                fn (string $file): string => $this->pathNormalizer->normalizePath($file),
                $filesystem->files($directory)
            );

            foreach ($directories as $dir) {
                $path                = new Path($dir);
                $directoriesToScan[] = $path;

                $properties = $this->getProperties($path);

                if ($properties instanceof DirectoryPathProperties) {
                    if ($this->isDirectoryMatchingFilters($path, $query, $extension, $minSize, $maxSize)) {
                        $matchedDirectories[] = new PathTreeDirectory(
                            $path,
                            ! empty($filesystem->directories((string) $path)),
                            $properties->visibility
                        );
                    }
                }
            }

            foreach ($files as $file) {
                $path       = new Path($file);
                $properties = $this->getProperties($path);

                if (! ($properties instanceof FilePathProperties)) {
                    continue;
                }

                if (! $this->isFileMatchingFilters($path, $properties, $query, $extension, $minSize, $maxSize)) {
                    continue;
                }

                try {
                    $publicUrl = $filesystem->url(ltrim($path->value, '/'));
                } catch (\Throwable $e) {
                    $publicUrl = null;
                }

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
        ?int $maxSize
    ): bool {
        // Directories don't have extension or size. If the user filters by them, directories shouldn't match.
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

    /**
     * @throws FileOperationException
     */
    private function isFileMatchingFilters(
        Path $path,
        FilePathProperties $properties,
        ?string $query,
        ?string $extension,
        ?int $minSize,
        ?int $maxSize
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
            $files       = $filesystem->files($normalizedPath);
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
                $relativePath  = substr($normalizedDir, strlen($normalizedSource) + 1);
                $filesystem->makeDirectory((string) Path::appendTo($destination, $relativePath));
            }

            $files = $filesystem->allFiles($normalizedSource);
            foreach ($files as $file) {
                $normalizedFile = $this->pathNormalizer->normalizePath($file);
                $relativePath   = substr($normalizedFile, strlen($normalizedSource) + 1);
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
            fn (string $dir): string => $this->pathNormalizer->normalizePath($dir),
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

    public function getDisk(): Disk
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
            $relativePath  = substr($normalizedDir, strlen($source) + 1);
            $filesystem->makeDirectory((string) Path::appendTo(new Path($destination), $relativePath));
        }

        $files = $filesystem->allFiles($source);
        foreach ($files as $file) {
            $normalizedFile = $this->pathNormalizer->normalizePath($file);
            $relativePath   = substr($normalizedFile, strlen($source) + 1);
            $filesystem->move($file, (string) Path::appendTo(new Path($destination), $relativePath));
        }

        $filesystem->deleteDirectory($source);
    }

    /**
     * /**
     * @param  array<int, array{from: Path, to: string}>  $renames
     * @return array<int, array{from: string, to: string}>
     *
     * @throws FileOperationException
     */
    public function bulkRename(array $renames): array
    {
        $this->assertNotReadOnly();

        $plan = $this->buildBulkRenamePlan($renames);

        $sourcePaths = array_map(fn (array $p): string => $this->pathNormalizer->normalizePath($p['from']->value), $plan);
        $hasOverlap  = false;
        foreach ($plan as $p) {
            $normTarget = $this->pathNormalizer->normalizePath($p['target']->value);
            if (in_array($normTarget, $sourcePaths, true)) {
                $hasOverlap = true;
                break;
            }
        }

        return $this->executeBulkRename($plan, $hasOverlap);
    }

    /**
     * @param  array<int, array{from: Path, to: string}>  $renames
     * @return array<int, array{from: Path, toName: string, target: Path}>
     *
     * @throws FileOperationException
     */
    private function buildBulkRenamePlan(array $renames): array
    {
        $plan = [];

        foreach ($renames as $rename) {
            $from           = $rename['from'];
            $toName         = $rename['to'];
            $normalizedFrom = $this->pathNormalizer->normalizePath($from->value);

            if (! $this->isDirectory($from) && ! $this->isFile($from)) {
                FileOperationException::throwWith(FileOperationError::FILE_NOT_FOUND);
            }

            $target = Path::appendTo(
                new Path(dirname($normalizedFrom)),
                $this->pathNormalizer->normalizePath($toName)
            );

            $plan[] = [
                'from'   => $from,
                'toName' => $toName,
                'target' => $target,
            ];
        }

        $targetPaths = array_map(fn (array $p): string => (string) $p['target'], $plan);
        if (count($targetPaths) !== count(array_unique($targetPaths))) {
            FileOperationException::throwWith(FileOperationError::FILE_ALREADY_EXISTS);
        }

        $sourcePaths = array_map(fn (array $p): string => $this->pathNormalizer->normalizePath($p['from']->value), $plan);
        foreach ($plan as $p) {
            $normTarget = $this->pathNormalizer->normalizePath($p['target']->value);
            if (! in_array($normTarget, $sourcePaths, true) && ($this->isDirectory($p['target']) || $this->isFile($p['target']))) {
                FileOperationException::throwWith(
                    $this->isDirectory($p['from'])
                        ? FileOperationError::DIRECTORY_ALREADY_EXISTS
                        : FileOperationError::FILE_ALREADY_EXISTS
                );
            }
        }

        return $plan;
    }

    /**
     * @param  array<int, array{from: Path, toName: string, target: Path}>  $plan
     * @return array<int, array{from: string, to: string}>
     */
    private function executeBulkRename(array $plan, bool $hasOverlap): array
    {
        $filesystem = $this->getStorage();
        $result     = [];

        if ($hasOverlap) {
            $tempPlan = [];
            foreach ($plan as $idx => $p) {
                $tempName   = sprintf('.tmp_bulk_%s_%d', uniqid(), $idx);
                $tempTarget = Path::appendTo(new Path(dirname($this->pathNormalizer->normalizePath($p['from']->value))), $tempName);
                $filesystem->move(
                    $this->pathNormalizer->normalizePath($p['from']->value),
                    $this->pathNormalizer->normalizePath($tempTarget->value)
                );
                $tempPlan[] = [
                    'temp'   => $tempTarget,
                    'target' => $p['target'],
                    'from'   => $p['from'],
                ];
            }

            foreach ($tempPlan as $tp) {
                $filesystem->move(
                    $this->pathNormalizer->normalizePath($tp['temp']->value),
                    $this->pathNormalizer->normalizePath($tp['target']->value)
                );
                $result[] = [
                    'from' => (string) $tp['from'],
                    'to'   => (string) $tp['target'],
                ];
            }
        } else {
            foreach ($plan as $p) {
                $this->rename($p['from'], $p['toName']);
                $result[] = [
                    'from' => (string) $p['from'],
                    'to'   => (string) $p['target'],
                ];
            }
        }

        return $result;
    }

    public function supportsZip(): bool
    {
        return extension_loaded('zip') && class_exists(\ZipArchive::class);
    }

    /**
     * @param  Path[]  $paths
     *
     * @throws FileOperationException
     */
    public function zip(array $paths, Path $destinationZip): Path
    {
        $this->assertCanZip($paths, $destinationZip);

        $tempZip = tempnam(sys_get_temp_dir(), 'sm_zip_');
        if ($tempZip === false) {
            FileOperationException::throwWith(FileOperationError::ARCHIVE_OPERATION_FAILED);
        }

        $zip = new \ZipArchive();
        $res = $zip->open($tempZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        if ($res !== true) {
            if (file_exists($tempZip)) {
                unlink($tempZip);
            }
            FileOperationException::throwWith(FileOperationError::ARCHIVE_OPERATION_FAILED);
        }

        $filesystem = $this->getStorage();
        foreach ($paths as $path) {
            $this->addPathToZip($zip, $filesystem, $path);
        }

        $zip->close();

        $normDest = $this->pathNormalizer->normalizePath($destinationZip->value);
        $fp       = fopen($tempZip, 'rb');
        if (is_resource($fp)) {
            $filesystem->writeStream($normDest, $fp);
            fclose($fp);
        }

        if (file_exists($tempZip)) {
            unlink($tempZip);
        }

        return $destinationZip;
    }

    /**
     * @param  Path[]  $paths
     *
     * @throws FileOperationException
     */
    private function assertCanZip(array $paths, Path $destinationZip): void
    {
        $this->assertNotReadOnly();

        if (! $this->supportsZip()) {
            FileOperationException::throwWith(FileOperationError::ZIP_EXTENSION_NOT_AVAILABLE);
        }

        if ($this->exists($destinationZip)) {
            FileOperationException::throwWith(FileOperationError::FILE_ALREADY_EXISTS);
        }

        foreach ($paths as $path) {
            if (! $this->exists($path)) {
                FileOperationException::throwWith(FileOperationError::FILE_NOT_FOUND);
            }
        }
    }

    private function addPathToZip(\ZipArchive $zip, Filesystem $filesystem, Path $path): void
    {
        $normPath = $this->pathNormalizer->normalizePath($path->value);

        if ($this->isFile($path)) {
            $stream = $filesystem->readStream($normPath);
            if (is_resource($stream)) {
                $content = stream_get_contents($stream);
                fclose($stream);
                if ($content !== false) {
                    $zip->addFromString(basename($normPath), $content);
                }
            }

            return;
        }

        $baseDirName = basename($normPath);
        $zip->addEmptyDir($baseDirName);

        $allFiles = $filesystem->allFiles($normPath);
        foreach ($allFiles as $file) {
            $normFile = $this->pathNormalizer->normalizePath($file);
            $relFile  = substr($normFile, strlen($normPath) + 1);
            $stream   = $filesystem->readStream($normFile);
            if (is_resource($stream)) {
                $content = stream_get_contents($stream);
                fclose($stream);
                if ($content !== false) {
                    $zip->addFromString($baseDirName . '/' . $relFile, $content);
                }
            }
        }

        $allDirs = $filesystem->allDirectories($normPath);
        foreach ($allDirs as $dir) {
            $normDir = $this->pathNormalizer->normalizePath($dir);
            $relDir  = substr($normDir, strlen($normPath) + 1);
            $zip->addEmptyDir($baseDirName . '/' . $relDir);
        }
    }

    /**
     * @throws FileOperationException
     */
    public function unzip(Path $archivePath, Path $destinationDir): void
    {
        $this->assertCanUnzip($archivePath);

        $filesystem  = $this->getStorage();
        $normArchive = $this->pathNormalizer->normalizePath($archivePath->value);

        $tempZip = tempnam(sys_get_temp_dir(), 'sm_unzip_');
        if ($tempZip === false) {
            FileOperationException::throwWith(FileOperationError::ARCHIVE_OPERATION_FAILED);
        }

        $sourceStream = $filesystem->readStream($normArchive);
        $destStream   = fopen($tempZip, 'wb');
        if (is_resource($sourceStream) && is_resource($destStream)) {
            stream_copy_to_stream($sourceStream, $destStream);
            fclose($destStream);
            fclose($sourceStream);
        }

        $zip = new \ZipArchive();
        $res = $zip->open($tempZip);
        if ($res !== true) {
            if (file_exists($tempZip)) {
                unlink($tempZip);
            }
            FileOperationException::throwWith(FileOperationError::INVALID_ARCHIVE);
        }

        $normDestDir = $this->pathNormalizer->normalizePath($destinationDir->value);
        if ($normDestDir !== '/' && ! $filesystem->exists($normDestDir)) {
            $filesystem->makeDirectory($normDestDir);
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);
            if ($entryName !== false) {
                $this->extractZipEntry($zip, $filesystem, $entryName, $normDestDir, $tempZip);
            }
        }

        $zip->close();
        if (file_exists($tempZip)) {
            unlink($tempZip);
        }
    }

    /**
     * @throws FileOperationException
     */
    private function assertCanUnzip(Path $archivePath): void
    {
        $this->assertNotReadOnly();

        if (! $this->supportsZip()) {
            FileOperationException::throwWith(FileOperationError::ZIP_EXTENSION_NOT_AVAILABLE);
        }

        if (! $this->exists($archivePath)) {
            FileOperationException::throwWith(FileOperationError::FILE_NOT_FOUND);
        }

        if (! str_ends_with(strtolower($archivePath->value), '.zip')) {
            FileOperationException::throwWith(FileOperationError::INVALID_ARCHIVE);
        }
    }

    /**
     * @throws FileOperationException
     */
    private function extractZipEntry(
        \ZipArchive $zip,
        Filesystem $filesystem,
        string $entryName,
        string $normDestDir,
        string $tempZip
    ): void {
        $cleanEntry = ltrim(str_replace('\\', '/', $entryName), '/');
        $parts      = explode('/', $cleanEntry);
        if (in_array('..', $parts, true)) {
            $zip->close();
            if (file_exists($tempZip)) {
                unlink($tempZip);
            }
            FileOperationException::throwWith(FileOperationError::INVALID_PATH);
        }

        $targetPath = $normDestDir === '/' ? '/' . $cleanEntry : $normDestDir . '/' . $cleanEntry;
        $targetPath = $this->pathNormalizer->normalizePath($targetPath);

        if (str_ends_with($entryName, '/')) {
            if (! $filesystem->exists($targetPath)) {
                $filesystem->makeDirectory($targetPath);
            }

            return;
        }

        $parentDir = dirname($targetPath);
        if ($parentDir !== '/' && ! $filesystem->exists($parentDir)) {
            $filesystem->makeDirectory($parentDir);
        }
        $entryStream = $zip->getStream($entryName);
        if ($entryStream !== false) {
            $filesystem->writeStream($targetPath, $entryStream);
            fclose($entryStream);
        }
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
