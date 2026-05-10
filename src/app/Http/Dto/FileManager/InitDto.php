<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Dto\FileManager;

use Kwaadpepper\LaravelStorageManager\Http\Dto\Dto;

final readonly class InitDto implements Dto
{
    public function jsonSerialize(): mixed
    {
        return [];
    }
}
