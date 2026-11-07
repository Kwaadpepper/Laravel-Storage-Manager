<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Controller;

use Illuminate\Routing\Controller;
use Kwaadpepper\LaravelStorageManager\Contract\Executor;
use Kwaadpepper\LaravelStorageManager\Http\Dto\FileManager\SearchResultDto;
use Kwaadpepper\LaravelStorageManager\Http\Request\SearchRequest;
use Kwaadpepper\LaravelStorageManager\Http\Response\ApiResponse;
use Kwaadpepper\LaravelStorageManager\Query\Search\SearchFilesInput;
use Kwaadpepper\LaravelStorageManager\Query\Search\SearchFilesQuery;

final class SearchController extends Controller
{
    public function __construct(
        private readonly Executor $executor,
        private readonly SearchFilesQuery $searchQuery,
    ) {
    }

    public function search(SearchRequest $request): ApiResponse
    {
        $searchResult = $this->executor->execute(
            $this->searchQuery,
            new SearchFilesInput(
                $request->getDisk(),
                $request->getPaths(),
                $request->getSearchQuery(),
                $request->getExtension(),
                $request->getMinSize(),
                $request->getMaxSize()
            )
        );

        return ApiResponse::json(
            new SearchResultDto($searchResult),
            ApiResponse::HTTP_OK
        );
    }
}
