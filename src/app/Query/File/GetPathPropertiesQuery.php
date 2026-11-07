<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Query\File;

use Kwaadpepper\LaravelStorageManager\Acl\SmAbility;
use Kwaadpepper\LaravelStorageManager\Contract\Query;
use Kwaadpepper\LaravelStorageManager\Contract\QueryInput;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\FileMetadataExtractor;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\FilePropertyExtractor;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathNormalizer;
use Kwaadpepper\LaravelStorageManager\Lib\StorageGateway;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathProperties;
use Psr\Log\LoggerInterface;

/**
 * @implements Query<GetPathPropertiesInput, PathProperties>
 */
final readonly class GetPathPropertiesQuery implements Query
{
    public function __construct(
        private PathNormalizer $pathNormalizer,
        private FilePropertyExtractor $filePropertyExtractor,
        private FileMetadataExtractor $fileMetadataExtractor,
        private StorageGateway $storageGateway,
        private LoggerInterface $logger,
    ) {
    }

    public function ability(): string
    {
        return SmAbility::VIEW;
    }

    /**
     * @param  GetPathPropertiesInput  $input
     *
     * @throws FileOperationException
     */
    public function __invoke(QueryInput $input): PathProperties
    {
        $this->logger->debug('Getting properties for path', ['disk' => $input->disk->name, 'path' => $input->path->value]);
        $normalizedPath    = $this->pathNormalizer->normalizePath($input->path->value);
        $sourceIsDir       = $this->storageGateway->resolveIsDirectory($input->disk, $input->path);
        $normalizedPathObj = new Path($normalizedPath);

        return $sourceIsDir
            ? $this->filePropertyExtractor->directoryProperties($input->disk, $normalizedPathObj)
            : $this->fileMetadataExtractor->extract($input->disk, $normalizedPathObj);
    }
}
