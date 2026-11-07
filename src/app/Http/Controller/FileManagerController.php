<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Controller;

use Illuminate\Routing\Controller;
use Kwaadpepper\LaravelStorageManager\Http\Dto\FileManager\InitDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\FileManager\PathContentDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\FileManager\PathTreeLevelDto;
use Kwaadpepper\LaravelStorageManager\Http\Request\RequestWithPath;
use Kwaadpepper\LaravelStorageManager\Http\Response\ApiResponse;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\FileManager;

final class FileManagerController extends Controller
{
    public function __construct(
        private readonly FileManager $fileManager,
    ) {
    }

    public function init(): ApiResponse
    {
        $initData = $this->fileManager->init();

        return ApiResponse::json(
            new InitDto($initData['chunkMinSize'], $initData['chunkMaxSize']),
            ApiResponse::HTTP_OK
        );
    }

    public function tree(RequestWithPath $request): ApiResponse
    {
        $fileTree = $this->fileManager->getPathTree($request->getDisk(), $request->getPath());

        return ApiResponse::json(
            new PathTreeLevelDto($fileTree),
            ApiResponse::HTTP_OK
        );
    }

    public function content(RequestWithPath $request): ApiResponse
    {
        $fileTree = $this->fileManager->getContent($request->getDisk(), $request->getPath());

        return ApiResponse::json(
            new PathContentDto($fileTree),
            ApiResponse::HTTP_OK
        );
    }
}
