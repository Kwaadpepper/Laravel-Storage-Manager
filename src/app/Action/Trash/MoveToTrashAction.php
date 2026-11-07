<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Action\Trash;

use Kwaadpepper\LaravelStorageManager\Acl\SmAbility;
use Kwaadpepper\LaravelStorageManager\Contract\Action;
use Kwaadpepper\LaravelStorageManager\Contract\ActionInput;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Trash\TrashedItem;
use Kwaadpepper\LaravelStorageManager\Service\TrashService;

/**
 * @implements Action<MoveToTrashInput, TrashedItem>
 */
final readonly class MoveToTrashAction implements Action
{
    public function __construct(
        private TrashService $trashService,
    ) {
    }

    public function ability(): string
    {
        return SmAbility::DELETE;
    }

    /**
     * @param  MoveToTrashInput  $input
     *
     * @throws FileOperationException
     */
    public function __invoke(ActionInput $input): TrashedItem
    {
        return $this->trashService->moveToTrash($input->disk, $input->path);
    }
}
