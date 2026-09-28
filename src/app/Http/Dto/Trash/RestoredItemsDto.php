<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Dto\Trash;

use Kwaadpepper\LaravelStorageManager\Http\Dto\Dto;

final readonly class RestoredItemsDto implements Dto
{
    /**
     * @param  list<array{id: string, path: string}>  $restored
     */
    public function __construct(
        public array $restored,
    ) {
    }

    /**
     * @return array{restored: list<array{id: string, path: string}>}
     */
    public function jsonSerialize(): mixed
    {
        return [
            'restored' => $this->restored,
        ];
    }
}
