<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Dto\BasicOperations;

use Kwaadpepper\LaravelStorageManager\Http\Dto\Dto;

final readonly class ExistsDto implements Dto
{
    public function __construct(
        public readonly bool $exists
    ) {
    }

    public function jsonSerialize(): mixed
    {
        return [
            'exists' => $this->exists,
        ];
    }
}
