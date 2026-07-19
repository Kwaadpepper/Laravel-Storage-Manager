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
use Kwaadpepper\LaravelStorageManager\Lib\Event\EventDispatcher;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\FileManager;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathList;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeLevel;
use Kwaadpepper\LaravelStorageManager\Repository\ConfigRepository;

final class FileManagerController extends Controller
{
    public function __construct(
        private readonly EventDispatcher $eventDispatcher,
        private readonly FileManager $fileManager,
        private readonly ConfigRepository $configRepository
    ) {
    }

    public function init(): ApiResponse
    {
        $this->eventDispatcher->dispatch(FileManagerShowed::class);

        $chunkMinSize = $this->configRepository->getUploadChunkMinSize();
        $chunkMaxSize = $this->configRepository->getUploadChunkMaxSize();

        return ApiResponse::json(
            $this->presentInit($chunkMinSize, $chunkMaxSize),
            ApiResponse::HTTP_OK
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

    private function presentInit(int $chunkMinSize, int $chunkMaxSize): InitDto
    {
        return new InitDto($chunkMinSize, $chunkMaxSize);
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
