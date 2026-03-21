<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Controller;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;
use Kwaadpepper\LaravelStorageManager\Http\Request\SelectDiskRequest;
use Kwaadpepper\LaravelStorageManager\Service\DiskService;
use Symfony\Component\HttpFoundation\JsonResponse;

class DiskController extends Controller
{
    public function __construct(
        private readonly DiskService $diskService
    ) {
    }

    public function list()
    {
        return Response::json([
            'disks' => $this->diskService->getDiskNamesList(),
        ]);
    }

    public function select(SelectDiskRequest $request): JsonResponse
    {
        $disk = $this->diskService->getDisk($request->string('disk')->value());

        return Response::json([
            'disk' => $disk,
        ]);
    }
}
