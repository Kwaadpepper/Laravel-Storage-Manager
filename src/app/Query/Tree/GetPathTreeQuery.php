<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Query\Tree;

use Kwaadpepper\LaravelStorageManager\Acl\SmAbility;
use Kwaadpepper\LaravelStorageManager\Contract\Query;
use Kwaadpepper\LaravelStorageManager\Contract\QueryInput;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\FilePropertyExtractor;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathNormalizer;
use Kwaadpepper\LaravelStorageManager\Lib\StorageGateway;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\DirectoryPathProperties;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\FilePathProperties;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeDirectory;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeFile;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeLevel;
use Kwaadpepper\LaravelStorageManager\Service\DirectoryListingService;
use Psr\Log\LoggerInterface;

/**
 * @implements Query<GetPathTreeInput, PathTreeLevel>
 */
final readonly class GetPathTreeQuery implements Query
{
    public function __construct(
        private PathNormalizer $pathNormalizer,
        private FilePropertyExtractor $filePropertyExtractor,
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

        $entries = $this->directoryListingService->listCleanEntries($input->disk, $directory);

        $directories = array_map(function (string $dir) use ($input): PathTreeDirectory {
            $dirPath    = new Path($dir);
            $properties = $this->filePropertyExtractor->directoryProperties($input->disk, $dirPath);

            if (! ($properties instanceof DirectoryPathProperties)) {
                throw new \LogicException("Expected directory properties for path '{$dirPath}', got file properties.");
            }

            return new PathTreeDirectory(
                $dirPath,
                $this->directoryListingService->hasSubdirectories($input->disk, (string) $dirPath),
                $properties->visibility
            );
        }, $entries['directories']);

        $files = array_map(function (string $file) use ($input): PathTreeFile {
            $filePath   = new Path($file);
            $properties = $this->filePropertyExtractor->fileProperties($input->disk, $filePath);

            if (! ($properties instanceof FilePathProperties)) {
                throw new \LogicException("Expected file properties for path '{$filePath}', got directory properties.");
            }

            $publicUrl = $this->storageGateway->publicUrl($input->disk, $filePath);

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
}
