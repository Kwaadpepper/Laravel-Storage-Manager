<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Query\Tree;

use Kwaadpepper\LaravelStorageManager\Acl\SmAbility;
use Kwaadpepper\LaravelStorageManager\Contract\Query;
use Kwaadpepper\LaravelStorageManager\Contract\QueryInput;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathNormalizer;
use Kwaadpepper\LaravelStorageManager\Lib\StorageGateway;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathVisibility;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeDirectory;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeFile;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeLevel;
use Kwaadpepper\LaravelStorageManager\Service\DirectoryListingService;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use Psr\Log\LoggerInterface;

/**
 * @implements Query<GetPathTreeInput, PathTreeLevel>
 */
final readonly class GetPathTreeQuery implements Query
{
    public function __construct(
        private PathNormalizer $pathNormalizer,
        private StorageGateway $storageGateway,
        private DirectoryListingService $directoryListingService,
        private LoggerInterface $logger,
    ) {
    }

    public function ability(): string
    {
        return SmAbility::VIEW;
    }

    /**
     * @param  GetPathTreeInput  $input
     */
    public function __invoke(QueryInput $input): PathTreeLevel
    {
        $directory = $this->pathNormalizer->normalizePath($input->path ? $input->path->value : '/');
        $this->logger->debug('Getting path tree', ['disk' => $input->disk->name, 'directory' => $directory]);

        if ($this->directoryListingService->isTrashPath($directory)) {
            return new PathTreeLevel([], []);
        }

        $cleanContents = $this->directoryListingService->listCleanContents($input->disk, $directory);

        $directories = array_map(function (DirectoryAttributes $dir) use ($input): PathTreeDirectory {
            $normalizedPath = $this->pathNormalizer->normalizePath($dir->path());
            $dirPath        = new Path($normalizedPath);
            $visibilityStr  = $dir->visibility();
            $visibility     = $visibilityStr !== null ? PathVisibility::tryFrom($visibilityStr) : null;

            return new PathTreeDirectory(
                $dirPath,
                $this->directoryListingService->hasSubdirectories($input->disk, (string) $dirPath),
                $visibility
            );
        }, $cleanContents->directories);

        $files = array_map(function (FileAttributes $file) use ($input): PathTreeFile {
            $normalizedPath = $this->pathNormalizer->normalizePath($file->path());
            $filePath       = new Path($normalizedPath);
            $size           = $file->fileSize() ?? 0;
            $extension      = pathinfo($normalizedPath, PATHINFO_EXTENSION);
            $visibilityStr  = $file->visibility();
            $visibility     = $visibilityStr !== null ? PathVisibility::tryFrom($visibilityStr) : null;
            $publicUrl      = $this->storageGateway->publicUrl($input->disk, $filePath);

            return new PathTreeFile(
                $filePath,
                $size,
                $extension,
                $visibility,
                $publicUrl
            );
        }, $cleanContents->files);

        return new PathTreeLevel(
            array_values($directories),
            array_values($files)
        );
    }
}
