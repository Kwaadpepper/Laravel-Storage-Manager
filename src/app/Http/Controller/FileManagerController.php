<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Controller;

use Illuminate\Routing\Controller;
use Kwaadpepper\LaravelStorageManager\Event\FileManagerShowed;
use Kwaadpepper\LaravelStorageManager\Http\Dto\FileManager\InitDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\FileManager\PathContentDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\FileManager\PathTreeLevelDto;
use Kwaadpepper\LaravelStorageManager\Http\Request\RequestWithPath;
use Kwaadpepper\LaravelStorageManager\Http\Response\ApiResponse;
use Kwaadpepper\LaravelStorageManager\Lib\Factory\EventFactory;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\FileManager;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathList;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeLevel;

final class FileManagerController extends Controller
{
    public function __construct(
        private readonly FileManager $fileManager
    ) {
    }

    public function init(): ApiResponse
    {
        EventFactory::dispatch(FileManagerShowed::class);

        return ApiResponse::json(
            $this->presentInit(),
            ApiResponse::HTTP_NO_CONTENT
        );
    }

    public function tree(RequestWithPath $request): ApiResponse
    {
        $currentPath = $request->getPath();
        $fileTree    = $this->fileManager->getPathTree($currentPath);

        return ApiResponse::json(
            $this->presentTree($fileTree),
            ApiResponse::HTTP_OK
        );
    }

    public function content(RequestWithPath $request): ApiResponse
    {
        $currentPath = $request->getPath();
        $fileTree    = $this->fileManager->getContent($currentPath);

        return ApiResponse::json(
            $this->presentContent($fileTree),
            ApiResponse::HTTP_OK
        );
    }

    private function presentInit(): InitDto
    {
        return new InitDto();
    }

    private function presentTree(PathTreeLevel $fileTree): PathTreeLevelDto
    {
        return new PathTreeLevelDto($fileTree);
    }

    private function presentContent(PathList $fileTree): PathContentDto
    {
        return new PathContentDto($fileTree);
    }
}
