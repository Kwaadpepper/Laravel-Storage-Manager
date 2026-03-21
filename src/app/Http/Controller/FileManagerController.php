<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Controller;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;
use Kwaadpepper\LaravelStorageManager\Event\FileManagerShowed;
use Kwaadpepper\LaravelStorageManager\Http\Request\FileManager\FmTreeRequest;
use Kwaadpepper\LaravelStorageManager\Lib\Factory\EventFactory;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path;
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

    public function tree(FmTreeRequest $request): JsonResponse
    {
        $currentPath = new Path($request->string('path')->value());
        $fileTree    = $this->fileManager->getFileTree($currentPath);

        return Response::json([
            'files'       => array_map(fn ($file) => (string) $file, $fileTree->files),
            'directories' => array_map(fn ($dir) => (string) $dir, $fileTree->directories),
        ], JsonResponse::HTTP_OK);
    }
}
