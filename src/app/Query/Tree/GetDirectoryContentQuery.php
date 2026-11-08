<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Query\Tree;

use Kwaadpepper\LaravelStorageManager\Acl\SmAbility;
use Kwaadpepper\LaravelStorageManager\Contract\Query;
use Kwaadpepper\LaravelStorageManager\Contract\QueryInput;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathNormalizer;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PaginatedDirectoryContent;
use Kwaadpepper\LaravelStorageManager\Service\DirectoryListingService;
use Psr\Log\LoggerInterface;

/**
 * @implements Query<GetDirectoryContentInput, PaginatedDirectoryContent>
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
    public function __invoke(QueryInput $input): PaginatedDirectoryContent
    {
        $directory = $this->pathNormalizer->normalizePath($input->path ? $input->path->value : '/');
        $this->logger->debug('Getting directory content', [
            'disk'          => $input->disk->name,
            'directory'     => $directory,
            'page'          => $input->pagination->pageNumber()->value,
            'perPage'       => $input->pagination->pageSize()->value,
            'sortBy'        => $input->sortBy?->value,
            'sortDirection' => $input->sortDirection->name,
        ]);

        return $this->directoryListingService->paginateCleanContents(
            $input->disk,
            $directory,
            $input->pagination,
            $input->sortBy,
            $input->sortDirection
        );
    }
}
