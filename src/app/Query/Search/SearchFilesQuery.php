<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Query\Search;

use Kwaadpepper\LaravelStorageManager\Acl\SmAbility;
use Kwaadpepper\LaravelStorageManager\Contract\Query;
use Kwaadpepper\LaravelStorageManager\Contract\QueryInput;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\FileSearchService;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\SearchResult;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Psr\Log\LoggerInterface;

/**
 * @implements Query<SearchFilesInput, SearchResult>
 */
final readonly class SearchFilesQuery implements Query
{
    public function __construct(
        private FileSearchService $fileSearchService,
        private LoggerInterface $logger,
    ) {
    }

    public function ability(): string
    {
        return SmAbility::SEARCH;
    }

    /**
     * @param  SearchFilesInput  $input
     */
    public function __invoke(QueryInput $input): SearchResult
    {
        $this->logger->debug('Delegating searchContent to FileSearchService', [
            'disk'      => $input->disk->name,
            'paths'     => array_map(fn (Path $p): string => $p->value, $input->paths),
            'query'     => $input->query,
            'extension' => $input->extension,
            'minSize'   => $input->minSize,
            'maxSize'   => $input->maxSize,
        ]);

        return $this->fileSearchService->search(
            $input->disk,
            $input->paths,
            $input->query,
            $input->extension,
            $input->minSize,
            $input->maxSize
        );
    }
}
