<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Dto\Trash;

use Kwaadpepper\LaravelStorageManager\Http\Dto\Dto;

final readonly class TrashListDto implements Dto
{
    /**
     * @param  list<TrashedItemDto>  $items
     */
    public function __construct(
        public array $items,
        public int $total,
    ) {
    }

    /**
     * @return array{
     *     items: list<array{id: string, name: string, originalPath: string, type: string, size: int, deletedAt: string, disk: string}>,
     *     total: int
     * }
     */
    public function jsonSerialize(): mixed
    {
        return [
            'items' => array_map(
                fn (TrashedItemDto $dto): array => $dto->jsonSerialize(),
                $this->items
            ),
            'total' => $this->total,
        ];
    }
}
