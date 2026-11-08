<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Controller;

use Illuminate\Routing\Controller;
use Kwaadpepper\LaravelStorageManager\Contract\Executor;
use Kwaadpepper\LaravelStorageManager\Http\Dto\Disk\DiskListDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\Disk\SelectedDiskDto;
use Kwaadpepper\LaravelStorageManager\Http\Request\Disk\DiskStatsRequest;
use Kwaadpepper\LaravelStorageManager\Http\Request\Disk\SelectDiskRequest;
use Kwaadpepper\LaravelStorageManager\Http\Response\ApiResponse;
use Kwaadpepper\LaravelStorageManager\Query\Disk\GetConfiguredDisksInput;
use Kwaadpepper\LaravelStorageManager\Query\Disk\GetConfiguredDisksQuery;
use Kwaadpepper\LaravelStorageManager\Query\Disk\GetDiskStatsInput;
use Kwaadpepper\LaravelStorageManager\Query\Disk\GetDiskStatsQuery;
use Kwaadpepper\LaravelStorageManager\Query\Disk\SelectDiskInput;
use Kwaadpepper\LaravelStorageManager\Query\Disk\SelectDiskQuery;

final class DiskController extends Controller
{
    public function __construct(
        private readonly Executor $executor,
        private readonly GetConfiguredDisksQuery $listQuery,
        private readonly SelectDiskQuery $selectQuery,
        private readonly GetDiskStatsQuery $statsQuery,
    ) {
    }

    public function list(): ApiResponse
    {
        $disks = $this->executor->execute($this->listQuery, new GetConfiguredDisksInput());

        return ApiResponse::json(
            new DiskListDto($disks),
            ApiResponse::HTTP_OK
        );
    }

    public function select(SelectDiskRequest $request): ApiResponse
    {
        $disk = $this->executor->execute(
            $this->selectQuery,
            new SelectDiskInput($request->getDisk())
        );

        return ApiResponse::json(
            new SelectedDiskDto(
                driver: $disk->driver,
                name: $disk->name,
                throw: $disk->throw,
                report: $disk->report,
                readOnly: $disk->readOnly,
                trash: $disk->trash,
                allowedExtensions: $disk->allowedExtensions,
                maxFileSize: $disk->maxFileSize,
                filenamePolicy: $disk->filenamePolicy->value,
                duplicatePolicy: $disk->duplicatePolicy?->value,
            ),
            ApiResponse::HTTP_OK
        );
    }

    public function stats(DiskStatsRequest $request): ApiResponse
    {
        $stats = $this->executor->execute(
            $this->statsQuery,
            new GetDiskStatsInput($request->getDisk(), $request->boolean('fresh'))
        );

        return ApiResponse::json(
            $stats,
            ApiResponse::HTTP_OK
        );
    }
}
