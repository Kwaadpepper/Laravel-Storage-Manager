<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Controller;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;
use Kwaadpepper\LaravelStorageManager\Event\FileManagerShowed;
use Kwaadpepper\LaravelStorageManager\Lib\Factory\EventFactory;
use Symfony\Component\HttpFoundation\JsonResponse;

final class FileManagerController extends Controller
{
    public function init(): JsonResponse
    {
        EventFactory::dispatch(FileManagerShowed::class);

        return Response::json([], JsonResponse::HTTP_NO_CONTENT);
    }
}
