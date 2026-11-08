<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\FileManager;

use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Lib\StorageGateway;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\FilePathProperties;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathVisibility;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeDirectory;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeFile;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use Psr\Log\LoggerInterface;

readonly class FileSearchService
{
    public function __construct(
        private PathNormalizer $pathNormalizer,
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
            $cleanContents           = $this->listCleanContents($disk, $directory);

            $this->processDirectories(
                $disk,
                $cleanContents['directories'],
                $lowercasedQuery,
                $hasFileSpecificFilters,
                $directoriesToScan,
                $matchedDirectories
            );

            $this->processFiles(
                $disk,
                $cleanContents['files'],
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
     * @param  list<DirectoryAttributes>  $directories
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
        foreach ($directories as $dirAttr) {
            $normalized          = $this->pathNormalizer->normalizePath($dirAttr->path());
            $path                = new Path($normalized);
            $directoriesToScan[] = $path;

            if ($hasFileSpecificFilters) {
                continue;
            }

            if (! $this->isNameMatchingQuery($path->value, $lowercasedQuery)) {
                continue;
            }

            $visibilityStr = $dirAttr->visibility();
            $visibility    = $visibilityStr !== null ? PathVisibility::tryFrom($visibilityStr) : null;

            $matchedDirectories[] = new PathTreeDirectory(
                $path,
                $this->hasSubdirectories((string) $path, $disk),
                $visibility
            );
        }
    }

    /**
     * @param  list<FileAttributes>  $files
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
        foreach ($files as $fileAttr) {
            $normalized = $this->pathNormalizer->normalizePath($fileAttr->path());
            $path       = new Path($normalized);

            if (! $this->isExtensionMatching($normalized, $lowercasedExtension)) {
                continue;
            }

            if (! $this->isNameMatchingQuery($normalized, $lowercasedQuery)) {
                continue;
            }

            $size = $fileAttr->fileSize() ?? 0;

            if (! $this->isSizeMatching($size, $minSize, $maxSize)) {
                continue;
            }

            $extension     = pathinfo($normalized, PATHINFO_EXTENSION);
            $visibilityStr = $fileAttr->visibility();
            $visibility    = $visibilityStr !== null ? PathVisibility::tryFrom($visibilityStr) : null;
            $publicUrl     = $this->storageGateway->publicUrl($disk, $path);

            $matchedFiles[] = new PathTreeFile(
                $path,
                $size,
                $extension,
                $visibility,
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
        if (! $disk->isLocal()) {
            return $this->storageGateway->hasSubdirectories($disk, new Path($path));
        }

        $directories = $this->storageGateway->directories($disk, $path);

        return ! empty(array_filter(
            $directories,
            fn (string $dir): bool => ! $this->isTrashPath($this->pathNormalizer->normalizePath($dir))
        ));
    }

    /**
     * @return array{directories: list<DirectoryAttributes>, files: list<FileAttributes>}
     */
    private function listCleanContents(Disk $disk, string $directory): array
    {
        $allAttributes = $this->storageGateway->listContents($disk, $directory);

        $directories = [];
        $files       = [];

        foreach ($allAttributes as $attributes) {
            $normalized = $this->pathNormalizer->normalizePath($attributes->path());

            if ($this->isTrashPath($normalized)) {
                continue;
            }

            if ($attributes instanceof DirectoryAttributes) {
                $directories[] = $attributes;
            } elseif ($attributes instanceof FileAttributes) {
                $files[] = $attributes;
            }
        }

        return [
            'directories' => $directories,
            'files'       => $files,
        ];
    }
}
