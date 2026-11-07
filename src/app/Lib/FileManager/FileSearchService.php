<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\FileManager;

use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Lib\StorageGateway;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\DirectoryPathProperties;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\FilePathProperties;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeDirectory;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeFile;
use Psr\Log\LoggerInterface;

readonly class FileSearchService
{
    public function __construct(
        private PathNormalizer $pathNormalizer,
        private FilePropertyExtractor $filePropertyExtractor,
        private StorageGateway $storageGateway,
        private StorageManagerConfig $config,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param  Path[]  $paths
     */
    public function search(
        Disk $disk,
        array $paths,
        ?string $query,
        ?string $extension,
        ?int $minSize,
        ?int $maxSize,
    ): SearchResult {
        $this->logger->debug('Executing file search', [
            'disk'      => $disk->name,
            'paths'     => array_map(fn (Path $p): string => $p->value, $paths),
            'query'     => $query,
            'extension' => $extension,
            'minSize'   => $minSize,
            'maxSize'   => $maxSize,
        ]);

        $directoriesToScan  = [];
        $matchedDirectories = [];
        $matchedFiles       = [];
        $scannedDirs        = [];

        $hasFileSpecificFilters = $extension !== null || $minSize !== null || $maxSize !== null;
        $lowercasedQuery        = $query     !== null ? mb_strtolower(trim($query)) : null;
        $lowercasedExtension    = $extension !== null ? mb_strtolower(ltrim(trim($extension), '.')) : null;

        foreach ($paths as $pathObj) {
            $directory = $this->pathNormalizer->normalizePath($pathObj->value);

            if ($this->isTrashPath($directory) || isset($scannedDirs[$directory])) {
                continue;
            }

            $scannedDirs[$directory] = true;
            $entries                 = $this->listCleanDirectoryEntries($disk, $directory);

            $this->processDirectories(
                $disk,
                $entries['directories'],
                $lowercasedQuery,
                $hasFileSpecificFilters,
                $directoriesToScan,
                $matchedDirectories
            );

            $this->processFiles(
                $disk,
                $entries['files'],
                $lowercasedQuery,
                $lowercasedExtension,
                $minSize,
                $maxSize,
                $matchedFiles
            );
        }

        return new SearchResult($directoriesToScan, $matchedDirectories, $matchedFiles);
    }

    /**
     * @param  list<string>  $directories
     * @param  list<Path>  $directoriesToScan
     * @param  list<PathTreeDirectory>  $matchedDirectories
     */
    private function processDirectories(
        Disk $disk,
        array $directories,
        ?string $lowercasedQuery,
        bool $hasFileSpecificFilters,
        array &$directoriesToScan,
        array &$matchedDirectories,
    ): void {
        foreach ($directories as $dir) {
            $path                = new Path($dir);
            $directoriesToScan[] = $path;

            if ($hasFileSpecificFilters) {
                continue;
            }

            if (! $this->isNameMatchingQuery($path->value, $lowercasedQuery)) {
                continue;
            }

            $properties = $this->filePropertyExtractor->directoryProperties($disk, $path);

            if ($properties instanceof DirectoryPathProperties) {
                $matchedDirectories[] = new PathTreeDirectory(
                    $path,
                    $this->hasSubdirectories((string) $path, $disk),
                    $properties->visibility
                );
            }
        }
    }

    /**
     * @param  list<string>  $files
     * @param  list<PathTreeFile>  $matchedFiles
     */
    private function processFiles(
        Disk $disk,
        array $files,
        ?string $lowercasedQuery,
        ?string $lowercasedExtension,
        ?int $minSize,
        ?int $maxSize,
        array &$matchedFiles,
    ): void {
        foreach ($files as $file) {
            $path = new Path($file);

            if (! $this->isExtensionMatching($file, $lowercasedExtension)) {
                continue;
            }

            if (! $this->isNameMatchingQuery($file, $lowercasedQuery)) {
                continue;
            }

            $properties = $this->filePropertyExtractor->fileProperties($disk, $path);

            if (! ($properties instanceof FilePathProperties)) {
                continue;
            }

            if (! $this->isSizeMatching($properties->size, $minSize, $maxSize)) {
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

    public function isDirectoryMatchingFilters(
        Path $path,
        ?string $query,
        ?string $extension,
        ?int $minSize,
        ?int $maxSize,
    ): bool {
        if ($extension !== null || $minSize !== null || $maxSize !== null) {
            return false;
        }

        $lowercasedQuery = $query !== null ? mb_strtolower(trim($query)) : null;

        return $this->isNameMatchingQuery($path->value, $lowercasedQuery);
    }

    public function isFileMatchingFilters(
        Path $path,
        FilePathProperties $properties,
        ?string $query,
        ?string $extension,
        ?int $minSize,
        ?int $maxSize,
    ): bool {
        $lowercasedExtension = $extension !== null ? mb_strtolower(ltrim(trim($extension), '.')) : null;
        if (! $this->isExtensionMatching($path->value, $lowercasedExtension, $properties->extension)) {
            return false;
        }

        if (! $this->isSizeMatching($properties->size, $minSize, $maxSize)) {
            return false;
        }

        $lowercasedQuery = $query !== null ? mb_strtolower(trim($query)) : null;

        return $this->isNameMatchingQuery($path->value, $lowercasedQuery);
    }

    private function isNameMatchingQuery(string $path, ?string $lowercasedQuery): bool
    {
        if ($lowercasedQuery === null || $lowercasedQuery === '') {
            return true;
        }

        $basename = mb_strtolower(basename($path));

        return str_contains($basename, $lowercasedQuery);
    }

    private function isExtensionMatching(string $path, ?string $lowercasedExtension, ?string $cachedExtension = null): bool
    {
        if ($lowercasedExtension === null || $lowercasedExtension === '') {
            return true;
        }

        $ext = $cachedExtension !== null
            ? mb_strtolower($cachedExtension)
            : mb_strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return $ext === $lowercasedExtension;
    }

    private function isSizeMatching(int $size, ?int $minSize, ?int $maxSize): bool
    {
        if ($minSize !== null && $size < $minSize) {
            return false;
        }

        if ($maxSize !== null && $size > $maxSize) {
            return false;
        }

        return true;
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
