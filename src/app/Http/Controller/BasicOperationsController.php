<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Kwaadpepper\LaravelStorageManager\Http\Request\BasicOperations\DeletePathRequest;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\FileManager;

class BasicOperationsController extends Controller
{
    public function __construct(
        private readonly FileManager $fileManager
    ) {
    }

    public function delete(DeletePathRequest $request): JsonResponse
    {
        $path = $request->getPath();

        $this->fileManager->delete($path);

        return JsonResponse::json([], JsonResponse::HTTP_NO_CONTENT);
    }
}
