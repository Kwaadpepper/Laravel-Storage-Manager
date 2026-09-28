<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Dto\Trash;

use Kwaadpepper\LaravelStorageManager\Http\Dto\Dto;

final readonly class PurgedTrashDto implements Dto
{
    /**
     * @param  list<string>  $ids
     */
    public function __construct(
        public array $ids,
        public int $count,
    ) {
    }

    /**
     * @return array{ids: list<string>, count: int}
     */
    public function jsonSerialize(): mixed
    {
        return [
            'ids'   => $this->ids,
            'count' => $this->count,
        ];
    }
}
