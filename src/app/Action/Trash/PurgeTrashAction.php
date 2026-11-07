<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Action\Trash;

use Kwaadpepper\LaravelStorageManager\Acl\SmAbility;
use Kwaadpepper\LaravelStorageManager\Contract\Action;
use Kwaadpepper\LaravelStorageManager\Contract\ActionInput;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Service\TrashService;

/**
 * @implements Action<PurgeTrashInput, list<string>>
 */
final readonly class PurgeTrashAction implements Action
{
    public function __construct(
        private TrashService $trashService,
    ) {
    }

    public function ability(): string
    {
        return SmAbility::TRASH_PURGE;
    }

    /**
     * @param  PurgeTrashInput  $input
     * @return list<string>
     *
     * @throws FileOperationException
     */
    public function __invoke(ActionInput $input): array
    {
        foreach ($input->ids as $id) {
            $this->trashService->purge($input->disk, $id);
        }

        return $input->ids;
    }
}
