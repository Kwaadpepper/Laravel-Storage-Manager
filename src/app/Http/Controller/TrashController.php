<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Controller;

use Illuminate\Routing\Controller;
use Kwaadpepper\LaravelStorageManager\Action\Trash\EmptyTrashAction;
use Kwaadpepper\LaravelStorageManager\Action\Trash\EmptyTrashInput;
use Kwaadpepper\LaravelStorageManager\Action\Trash\PurgeTrashAction;
use Kwaadpepper\LaravelStorageManager\Action\Trash\PurgeTrashInput;
use Kwaadpepper\LaravelStorageManager\Action\Trash\RestoreTrashAction;
use Kwaadpepper\LaravelStorageManager\Action\Trash\RestoreTrashInput;
use Kwaadpepper\LaravelStorageManager\Contract\Executor;
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
use Kwaadpepper\LaravelStorageManager\Query\Trash\ListTrashInput;
use Kwaadpepper\LaravelStorageManager\Query\Trash\ListTrashQuery;

class TrashController extends Controller
{
    public function __construct(
        private readonly Executor $executor,
        private readonly ListTrashQuery $listQuery,
        private readonly RestoreTrashAction $restoreAction,
        private readonly PurgeTrashAction $purgeAction,
        private readonly EmptyTrashAction $emptyAction,
    ) {
    }

    public function list(ListTrashRequest $request): ApiResponse
    {
        $items = $this->executor->execute(
            $this->listQuery,
            new ListTrashInput($request->getDisk())
        );

        $dtos = array_map(
            fn (TrashedItem $item): TrashedItemDto => TrashedItemDto::fromValueObject($item),
            $items
        );

        return ApiResponse::json(new TrashListDto($dtos, count($dtos)), ApiResponse::HTTP_OK);
    }

    public function restore(RestoreTrashRequest $request): ApiResponse
    {
        $restored = $this->executor->execute(
            $this->restoreAction,
            new RestoreTrashInput($request->getDisk(), $request->getIds())
        );

        return ApiResponse::json(new RestoredItemsDto($restored), ApiResponse::HTTP_OK);
    }

    public function purge(PurgeTrashRequest $request): ApiResponse
    {
        $ids = $this->executor->execute(
            $this->purgeAction,
            new PurgeTrashInput($request->getDisk(), $request->getIds())
        );

        return ApiResponse::json(new PurgedTrashDto($ids, count($ids)), ApiResponse::HTTP_OK);
    }

    public function empty(EmptyTrashRequest $request): ApiResponse
    {
        $count = $this->executor->execute(
            $this->emptyAction,
            new EmptyTrashInput($request->getDisk())
        );

        return ApiResponse::json(new PurgedTrashDto([], $count), ApiResponse::HTTP_OK);
    }
}
