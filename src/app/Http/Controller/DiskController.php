<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Controller;

use Illuminate\Routing\Controller;
use Kwaadpepper\LaravelStorageManager\Event\DiskSelected;
use Kwaadpepper\LaravelStorageManager\Http\Dto\Disk\DiskListDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\Disk\SelectedDiskDto;
use Kwaadpepper\LaravelStorageManager\Http\Request\Disk\SelectDiskRequest;
use Kwaadpepper\LaravelStorageManager\Http\Response\ApiResponse;
use Kwaadpepper\LaravelStorageManager\Lib\Event\EventDispatcher;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Service\DiskService;

final class DiskController extends Controller
{
    public function __construct(
        private readonly EventDispatcher $eventDispatcher,
        private readonly DiskService $diskService
    ) {
    }

    public function list(): ApiResponse
    {
        return ApiResponse::json(
            $this->presentList($this->diskService->getDiskNamesList()),
            ApiResponse::HTTP_OK
        );
    }

    public function select(SelectDiskRequest $request): ApiResponse
    {
        $this->eventDispatcher->dispatch(DiskSelected::class);

        $disk = $request->getDisk();

        return ApiResponse::json(
            $this->presentSelected($disk),
            ApiResponse::HTTP_OK
        );
    }

    /**
     * @param  list<string>  $diskNames
     */
    private function presentList(array $diskNames): DiskListDto
    {
        return new DiskListDto($diskNames);
    }

    private function presentSelected(Disk $disk): SelectedDiskDto
    {
        return new SelectedDiskDto(
            driver: $disk->driver,
            name: $disk->name,
            throw: $disk->throw,
            report: $disk->report
        );
    }
}
