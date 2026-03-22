<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Controller;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;
use Kwaadpepper\LaravelStorageManager\Event\FileManagerShowed;
use Kwaadpepper\LaravelStorageManager\Http\Request\FileManager\RequestWithPath;
use Kwaadpepper\LaravelStorageManager\Lib\Factory\EventFactory;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\FileManager;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathList;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeDirectory;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeLevel;
use Symfony\Component\HttpFoundation\JsonResponse;

final class FileManagerController extends Controller
{
    public function __construct(
        private readonly FileManager $fileManager
    ) {
    }

    public function init(): JsonResponse
    {
        EventFactory::dispatch(FileManagerShowed::class);

        return Response::json([], JsonResponse::HTTP_NO_CONTENT);
    }

    public function tree(RequestWithPath $request): JsonResponse
    {
        $currentPath = $request->getPath();
        $fileTree    = $this->fileManager->getPathTree($currentPath);

        return Response::json(
            $this->presentTree($fileTree),
            JsonResponse::HTTP_OK
        );
    }

    public function content(RequestWithPath $request): JsonResponse
    {
        $currentPath = $request->getPath();
        $fileTree    = $this->fileManager->getContent($currentPath);

        return Response::json(
            $this->presentContent($fileTree),
            JsonResponse::HTTP_OK
        );
    }

    private function presentTree(PathTreeLevel $fileTree): array
    {
        return [
            'directories' => array_map(fn (PathTreeDirectory $dir) => [
                'path'              => $dir->path,
                'hasSubDirectories' => $dir->hasSubDirectories,
            ], $fileTree->directories),
        ];
    }

    private function presentContent(PathList $fileTree): array
    {
        return [
            'files'       => array_map(fn ($file) => (string) $file, $fileTree->files),
            'directories' => array_map(fn ($dir) => (string) $dir, $fileTree->directories),
        ];
    }
}
