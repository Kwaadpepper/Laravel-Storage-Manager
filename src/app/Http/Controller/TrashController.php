<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Controller;

use Illuminate\Routing\Controller;
use Kwaadpepper\LaravelStorageManager\Http\Dto\Trash\PurgedTrashDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\Trash\RestoredItemsDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\Trash\TrashedItemDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\Trash\TrashListDto;
use Kwaadpepper\LaravelStorageManager\Http\Request\Trash\EmptyTrashRequest;
use Kwaadpepper\LaravelStorageManager\Http\Request\Trash\ListTrashRequest;
use Kwaadpepper\LaravelStorageManager\Http\Request\Trash\PurgeTrashRequest;
use Kwaadpepper\LaravelStorageManager\Http\Request\Trash\RestoreTrashRequest;
use Kwaadpepper\LaravelStorageManager\Http\Response\ApiResponse;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Trash\TrashedItem;
use Kwaadpepper\LaravelStorageManager\Service\TrashService;

class TrashController extends Controller
{
    public function __construct(
        private readonly TrashService $trashService,
    ) {
    }

    public function list(ListTrashRequest $request): ApiResponse
    {
        $disk  = $request->getDisk();
        $items = $this->trashService->list($disk);

        $dtos = array_map(
            fn (TrashedItem $item): TrashedItemDto => TrashedItemDto::fromValueObject($item),
            $items
        );

        return ApiResponse::json(new TrashListDto($dtos, count($dtos)), ApiResponse::HTTP_OK);
    }

    public function restore(RestoreTrashRequest $request): ApiResponse
    {
        $disk     = $request->getDisk();
        $ids      = $request->getIds();
        $restored = [];

        foreach ($ids as $id) {
            $restoredPath = $this->trashService->restore($disk, $id);
            $restored[]   = [
                'id'   => $id,
                'path' => $restoredPath->value,
            ];
        }

        return ApiResponse::json(new RestoredItemsDto($restored), ApiResponse::HTTP_OK);
    }

    public function purge(PurgeTrashRequest $request): ApiResponse
    {
        $disk = $request->getDisk();
        $ids  = $request->getIds();

        foreach ($ids as $id) {
            $this->trashService->purge($disk, $id);
        }

        return ApiResponse::json(new PurgedTrashDto($ids, count($ids)), ApiResponse::HTTP_OK);
    }

    public function empty(EmptyTrashRequest $request): ApiResponse
    {
        $disk  = $request->getDisk();
        $count = $this->trashService->emptyTrash($disk);

        return ApiResponse::json(new PurgedTrashDto([], $count), ApiResponse::HTTP_OK);
    }
}
