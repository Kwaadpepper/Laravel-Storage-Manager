<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Controller;

use Illuminate\Routing\Controller;
use Kwaadpepper\LaravelStorageManager\Contract\Executor;
use Kwaadpepper\LaravelStorageManager\Http\Dto\FileManager\InitDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\FileManager\PathContentDto;
use Kwaadpepper\LaravelStorageManager\Http\Request\RequestWithPath;
use Kwaadpepper\LaravelStorageManager\Http\Response\ApiResponse;
use Kwaadpepper\LaravelStorageManager\Query\Init\InitFileManagerInput;
use Kwaadpepper\LaravelStorageManager\Query\Init\InitFileManagerQuery;
use Kwaadpepper\LaravelStorageManager\Query\Tree\GetDirectoryContentInput;
use Kwaadpepper\LaravelStorageManager\Query\Tree\GetDirectoryContentQuery;

final class FileManagerController extends Controller
{
    public function __construct(
        private readonly Executor $executor,
        private readonly InitFileManagerQuery $initQuery,
        private readonly GetDirectoryContentQuery $contentQuery,
    ) {
    }

    public function init(): ApiResponse
    {
        $initData = $this->executor->execute($this->initQuery, new InitFileManagerInput());

        return ApiResponse::json(
            new InitDto($initData['chunkMinSize'], $initData['chunkMaxSize']),
            ApiResponse::HTTP_OK
        );
    }

    public function content(RequestWithPath $request): ApiResponse
    {
        $content = $this->executor->execute(
            $this->contentQuery,
            new GetDirectoryContentInput(
                $request->getDisk(),
                $request->getPath(),
                $request->getPagination(),
                $request->getSortBy(),
                $request->getSortDirection(),
            )
        );

        return ApiResponse::json(
            new PathContentDto($content),
            ApiResponse::HTTP_OK
        );
    }
}
