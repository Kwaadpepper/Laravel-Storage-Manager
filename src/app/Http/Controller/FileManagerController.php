<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Controller;

use Illuminate\Routing\Controller;
use Kwaadpepper\LaravelStorageManager\Contract\Executor;
use Kwaadpepper\LaravelStorageManager\Http\Dto\FileManager\InitDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\FileManager\PathContentDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\FileManager\PathTreeLevelDto;
use Kwaadpepper\LaravelStorageManager\Http\Request\RequestWithPath;
use Kwaadpepper\LaravelStorageManager\Http\Response\ApiResponse;
use Kwaadpepper\LaravelStorageManager\Query\Init\InitFileManagerInput;
use Kwaadpepper\LaravelStorageManager\Query\Init\InitFileManagerQuery;
use Kwaadpepper\LaravelStorageManager\Query\Tree\GetDirectoryContentInput;
use Kwaadpepper\LaravelStorageManager\Query\Tree\GetDirectoryContentQuery;
use Kwaadpepper\LaravelStorageManager\Query\Tree\GetPathTreeInput;
use Kwaadpepper\LaravelStorageManager\Query\Tree\GetPathTreeQuery;

final class FileManagerController extends Controller
{
    public function __construct(
        private readonly Executor $executor,
        private readonly InitFileManagerQuery $initQuery,
        private readonly GetPathTreeQuery $treeQuery,
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

    public function tree(RequestWithPath $request): ApiResponse
    {
        $fileTree = $this->executor->execute(
            $this->treeQuery,
            new GetPathTreeInput($request->getDisk(), $request->getPath())
        );

        return ApiResponse::json(
            new PathTreeLevelDto($fileTree),
            ApiResponse::HTTP_OK
        );
    }

    public function content(RequestWithPath $request): ApiResponse
    {
        $fileTree = $this->executor->execute(
            $this->contentQuery,
            new GetDirectoryContentInput($request->getDisk(), $request->getPath())
        );

        return ApiResponse::json(
            new PathContentDto($fileTree),
            ApiResponse::HTTP_OK
        );
    }
}
