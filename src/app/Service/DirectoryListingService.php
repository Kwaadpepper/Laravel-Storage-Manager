<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Service;

use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Enum\FileSortField;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathNormalizer;
use Kwaadpepper\LaravelStorageManager\Lib\StorageGateway;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Pagination\Pagination;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathVisibility;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\DirectoryListingResult;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PaginatedDirectoryContent;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PaginationMeta;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeDirectory;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeFile;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use SortDirection;

final readonly class DirectoryListingService
{
    public function __construct(
        private PathNormalizer $pathNormalizer,
        private StorageGateway $storageGateway,
        private StorageManagerConfig $config,
    ) {
    }

    public function isTrashPath(string $normalizedPath): bool
    {
        $trashFolder = '/' . trim($this->config->trash->folder, '/');

        return $normalizedPath === $trashFolder || str_starts_with($normalizedPath, $trashFolder . '/');
    }

    public function hasSubdirectories(Disk $disk, string $path): bool
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

    public function listCleanContents(Disk $disk, string $directory): DirectoryListingResult
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

        return new DirectoryListingResult($directories, $files);
    }

    /**
     * @return array{directories: list<string>, files: list<string>}
     */
    public function listCleanEntries(Disk $disk, string $directory): array
    {
        $contents = $this->listCleanContents($disk, $directory);

        $directories = array_map(
            fn (DirectoryAttributes $dir): string => $this->pathNormalizer->normalizePath($dir->path()),
            $contents->directories
        );

        $files = array_map(
            fn (FileAttributes $file): string => $this->pathNormalizer->normalizePath($file->path()),
            $contents->files
        );

        return [
            'directories' => array_values($directories),
            'files'       => array_values($files),
        ];
    }

    public function paginateCleanContents(
        Disk $disk,
        string $directory,
        Pagination $pagination,
        ?FileSortField $sortBy = null,
        SortDirection $sortDirection = SortDirection::Ascending,
    ): PaginatedDirectoryContent {
        $normalizedDirectory = $this->pathNormalizer->normalizePath($directory);
        if ($this->isTrashPath($normalizedDirectory)) {
            return new PaginatedDirectoryContent(
                [],
                [],
                PaginationMeta::fromPagination($pagination, 0, 0)
            );
        }

        $contents = $this->listCleanContents($disk, $normalizedDirectory);

        $totalDirectories = count($contents->directories);
        $totalFiles       = count($contents->files);

        $directories = $pagination->pageNumber()->value === 1
            ? $this->mapDirectories($disk, $contents->directories)
            : [];

        $sortedFiles = $this->sortFiles($contents->files, $sortBy, $sortDirection);
        $slicedFiles = array_slice($sortedFiles, $pagination->offset(), $pagination->limit());
        $fileObjects = $this->mapFiles($disk, $slicedFiles);

        $paginationMeta = PaginationMeta::fromPagination($pagination, $totalFiles, $totalDirectories);

        return new PaginatedDirectoryContent(
            $directories,
            $fileObjects,
            $paginationMeta
        );
    }

    /**
     * @param  DirectoryAttributes[]  $rawDirs
     * @return list<PathTreeDirectory>
     */
    private function mapDirectories(Disk $disk, array $rawDirs): array
    {
        usort($rawDirs, fn (DirectoryAttributes $a, DirectoryAttributes $b): int => strcmp(
            basename($this->pathNormalizer->normalizePath($a->path())),
            basename($this->pathNormalizer->normalizePath($b->path()))
        ));

        return array_values(array_map(function (DirectoryAttributes $dir) use ($disk): PathTreeDirectory {
            $normalizedPath = $this->pathNormalizer->normalizePath($dir->path());
            $dirPath        = new Path($normalizedPath);
            $visibilityStr  = $dir->visibility();
            $visibility     = $visibilityStr !== null ? PathVisibility::tryFrom($visibilityStr) : null;

            return new PathTreeDirectory(
                $dirPath,
                $this->hasSubdirectories($disk, (string) $dirPath),
                $visibility
            );
        }, $rawDirs));
    }

    /**
     * @param  FileAttributes[]  $files
     * @return FileAttributes[]
     */
    private function sortFiles(array $files, ?FileSortField $sortBy, SortDirection $sortDirection): array
    {
        $order = $sortDirection === SortDirection::Descending ? -1 : 1;

        usort($files, fn (FileAttributes $a, FileAttributes $b): int => $this->compareFiles($a, $b, $sortBy, $order));

        return $files;
    }

    private function compareFiles(FileAttributes $a, FileAttributes $b, ?FileSortField $sortBy, int $order): int
    {
        $pathA = $this->pathNormalizer->normalizePath($a->path());
        $pathB = $this->pathNormalizer->normalizePath($b->path());

        $result = $this->compareByField($a, $b, $sortBy, $pathA, $pathB);
        if ($result !== 0) {
            return $result * $order;
        }

        return strcmp(basename($pathA), basename($pathB)) * $order;
    }

    private function compareByField(
        FileAttributes $a,
        FileAttributes $b,
        ?FileSortField $sortBy,
        string $pathA,
        string $pathB,
    ): int {
        return match ($sortBy) {
            FileSortField::Size         => $this->compareInts((int) $a->fileSize(), (int) $b->fileSize()),
            FileSortField::Extension    => strcmp(pathinfo($pathA, PATHINFO_EXTENSION), pathinfo($pathB, PATHINFO_EXTENSION)),
            FileSortField::Visibility   => strcmp((string) $a->visibility(), (string) $b->visibility()),
            FileSortField::LastModified => $this->compareInts((int) $a->lastModified(), (int) $b->lastModified()),
            default                     => 0,
        };
    }

    private function compareInts(int $a, int $b): int
    {
        return $a <=> $b;
    }

    /**
     * @param  FileAttributes[]  $slicedFiles
     * @return list<PathTreeFile>
     */
    private function mapFiles(Disk $disk, array $slicedFiles): array
    {
        return array_values(array_map(function (FileAttributes $file) use ($disk): PathTreeFile {
            $normalizedPath = $this->pathNormalizer->normalizePath($file->path());
            $filePath       = new Path($normalizedPath);
            $size           = $file->fileSize() ?? 0;
            $extension      = pathinfo($normalizedPath, PATHINFO_EXTENSION);
            $visibilityStr  = $file->visibility();
            $visibility     = $visibilityStr !== null ? PathVisibility::tryFrom($visibilityStr) : null;
            $publicUrl      = $this->storageGateway->publicUrl($disk, $filePath);

            return new PathTreeFile(
                $filePath,
                $size,
                $extension,
                $visibility,
                $publicUrl
            );
        }, $slicedFiles));
    }
}
