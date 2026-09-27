<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Dto\BasicOperations;

use Kwaadpepper\LaravelStorageManager\Http\Dto\Dto;

final readonly class BulkRenamedDto implements Dto
{
    /**
     * @param  array<int, array{from: string, to: string}>  $renamed
     */
    public function __construct(
        public array $renamed = []
    ) {
    }

    /**
     * @return array{renamed: array<int, array{from: string, to: string}>}
     */
    public function jsonSerialize(): mixed
    {
        return [
            'renamed' => $this->renamed,
        ];
    }
}
