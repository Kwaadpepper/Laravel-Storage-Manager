<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Action\Trash;

use Kwaadpepper\LaravelStorageManager\Acl\SmAbility;
use Kwaadpepper\LaravelStorageManager\Contract\Action;
use Kwaadpepper\LaravelStorageManager\Contract\ActionInput;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Service\TrashService;

/**
 * @implements Action<RestoreTrashInput, list<array{id: string, path: string}>>
 */
final readonly class RestoreTrashAction implements Action
{
    public function __construct(
        private TrashService $trashService,
    ) {
    }

    public function ability(): string
    {
        return SmAbility::TRASH_RESTORE;
    }

    /**
     * @param  RestoreTrashInput  $input
     * @return list<array{id: string, path: string}>
     *
     * @throws FileOperationException
     */
    public function __invoke(ActionInput $input): array
    {
        $restored = [];

        foreach ($input->ids as $id) {
            $restoredPath = $this->trashService->restore($input->disk, $id);
            $restored[]   = [
                'id'   => $id,
                'path' => $restoredPath->value,
            ];
        }

        return $restored;
    }
}
