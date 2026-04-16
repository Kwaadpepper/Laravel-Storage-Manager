<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Dto\BasicOperations;

use Kwaadpepper\LaravelStorageManager\Http\Dto\Dto;

final readonly class CreatedDirectoryDto implements Dto
{
    public function jsonSerialize(): mixed
    {
        return [];
    }
}
