<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Query\Tree;

use Kwaadpepper\LaravelStorageManager\Acl\SmAbility;
use Kwaadpepper\LaravelStorageManager\Contract\Query;
use Kwaadpepper\LaravelStorageManager\Contract\QueryInput;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathNormalizer;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathList as PathContent;
use Kwaadpepper\LaravelStorageManager\Service\DirectoryListingService;
use Psr\Log\LoggerInterface;

/**
 * @implements Query<GetDirectoryContentInput, PathContent>
 */
final readonly class GetDirectoryContentQuery implements Query
{
    public function __construct(
        private PathNormalizer $pathNormalizer,
        private DirectoryListingService $directoryListingService,
        private LoggerInterface $logger,
    ) {
    }

    public function ability(): string
    {
        return SmAbility::VIEW;
    }

    /**
     * @param  GetDirectoryContentInput  $input
     */
    public function __invoke(QueryInput $input): PathContent
    {
        $directory = $this->pathNormalizer->normalizePath($input->path ? $input->path->value : '/');
        $this->logger->debug('Getting directory content', ['disk' => $input->disk->name, 'directory' => $directory]);

        if ($this->directoryListingService->isTrashPath($directory)) {
            return new PathContent([], []);
        }

        $entries = $this->directoryListingService->listCleanEntries($input->disk, $directory);

        $files       = array_map(fn (string $file): Path => new Path($file), $entries['files']);
        $directories = array_map(fn (string $dir): Path => new Path($dir), $entries['directories']);

        return new PathContent(
            array_values($files),
            array_values($directories),
        );
    }
}
