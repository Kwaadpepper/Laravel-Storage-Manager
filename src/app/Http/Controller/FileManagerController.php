<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Controller;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;
use Kwaadpepper\LaravelStorageManager\Event\FileManagerShowed;
use Symfony\Component\HttpFoundation\JsonResponse;

final class FileManagerController extends Controller
{
    public function init(): JsonResponse
    {
        dispatch(new FileManagerShowed());

        return Response::json([], JsonResponse::HTTP_NO_CONTENT);
    }
}
