<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Controller;

use Illuminate\Routing\Controller;
use Kwaadpepper\LaravelStorageManager\Http\Dto\FileManager\SearchResultDto;
use Kwaadpepper\LaravelStorageManager\Http\Request\SearchRequest;
use Kwaadpepper\LaravelStorageManager\Http\Response\ApiResponse;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\FileManager;

final class SearchController extends Controller
{
    public function __construct(
        private readonly FileManager $fileManager,
    ) {
    }

    public function search(SearchRequest $request): ApiResponse
    {
        $searchResult = $this->fileManager->searchContent(
            $request->getDisk(),
            $request->getPaths(),
            $request->getSearchQuery(),
            $request->getExtension(),
            $request->getMinSize(),
            $request->getMaxSize()
        );

        return ApiResponse::json(
            new SearchResultDto($searchResult),
            ApiResponse::HTTP_OK
        );
    }
}
