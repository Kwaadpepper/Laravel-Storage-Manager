<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Controller;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;
use Kwaadpepper\LaravelStorageManager\Event\DiskSelected;
use Kwaadpepper\LaravelStorageManager\Http\Request\Disk\SelectDiskRequest;
use Kwaadpepper\LaravelStorageManager\Lib\Factory\EventFactory;
use Kwaadpepper\LaravelStorageManager\Service\DiskService;
use Symfony\Component\HttpFoundation\JsonResponse;

final class DiskController extends Controller
{
    public function __construct(
        private readonly DiskService $diskService
    ) {
    }

    public function list(): JsonResponse
    {
        return Response::json([
            'disks' => $this->diskService->getDiskNamesList(),
        ], JsonResponse::HTTP_OK);
    }

    public function select(SelectDiskRequest $request): JsonResponse
    {
        EventFactory::dispatch(DiskSelected::class);

        $disk = $this->diskService->getDisk($request->string('disk')->value());

        return Response::json([
            'disk' => $disk,
        ], JsonResponse::HTTP_OK);
    }
}
