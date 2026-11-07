<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Action\Trash;

use Kwaadpepper\LaravelStorageManager\Acl\SmAbility;
use Kwaadpepper\LaravelStorageManager\Contract\Action;
use Kwaadpepper\LaravelStorageManager\Contract\ActionInput;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Service\TrashService;

/**
 * @implements Action<EmptyTrashInput, int>
 */
final readonly class EmptyTrashAction implements Action
{
    public function __construct(
        private TrashService $trashService,
    ) {
    }

    public function ability(): string
    {
        return SmAbility::TRASH_EMPTY;
    }

    /**
     * @param  EmptyTrashInput  $input
     *
     * @throws FileOperationException
     */
    public function __invoke(ActionInput $input): int
    {
        return $this->trashService->emptyTrash($input->disk);
    }
}
