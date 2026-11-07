<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Controller;

use Illuminate\Routing\Controller;
use Kwaadpepper\LaravelStorageManager\Http\Dto\Disk\DiskListDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\Disk\SelectedDiskDto;
use Kwaadpepper\LaravelStorageManager\Http\Request\Disk\DiskStatsRequest;
use Kwaadpepper\LaravelStorageManager\Http\Request\Disk\SelectDiskRequest;
use Kwaadpepper\LaravelStorageManager\Http\Response\ApiResponse;
use Kwaadpepper\LaravelStorageManager\Service\DiskService;

final class DiskController extends Controller
{
    public function __construct(
        private readonly DiskService $diskService,
    ) {
    }

    public function list(): ApiResponse
    {
        return ApiResponse::json(
            new DiskListDto($this->diskService->getDiskNamesList()),
            ApiResponse::HTTP_OK
        );
    }

    public function select(SelectDiskRequest $request): ApiResponse
    {
        $disk = $this->diskService->selectDisk($request->getDisk());

        return ApiResponse::json(
            new SelectedDiskDto(
                driver: $disk->driver,
                name: $disk->name,
                throw: $disk->throw,
                report: $disk->report
            ),
            ApiResponse::HTTP_OK
        );
    }

    public function stats(DiskStatsRequest $request): ApiResponse
    {
        $disk = $request->getDisk();

        if ($request->boolean('fresh')) {
            $this->diskService->clearDiskStatsCache($disk->name);
        }

        return ApiResponse::json(
            $this->diskService->getDiskStats($disk),
            ApiResponse::HTTP_OK
        );
    }
}
