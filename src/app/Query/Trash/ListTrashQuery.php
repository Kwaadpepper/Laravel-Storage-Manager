<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Query\Trash;

use Kwaadpepper\LaravelStorageManager\Acl\SmAbility;
use Kwaadpepper\LaravelStorageManager\Contract\Query;
use Kwaadpepper\LaravelStorageManager\Contract\QueryInput;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Trash\TrashedItem;
use Kwaadpepper\LaravelStorageManager\Service\TrashService;

/**
 * @implements Query<ListTrashInput, list<TrashedItem>>
 */
final readonly class ListTrashQuery implements Query
{
    public function __construct(
        private TrashService $trashService,
    ) {
    }

    public function ability(): string
    {
        return SmAbility::TRASH_LIST;
    }

    /**
     * @param  ListTrashInput  $input
     * @return list<TrashedItem>
     */
    public function __invoke(QueryInput $input): array
    {
        return $this->trashService->list($input->disk);
    }
}
