<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Dto\FileManager;

use Kwaadpepper\LaravelStorageManager\Http\Dto\Dto;

final readonly class InitDto implements Dto
{
    public function __construct(
        public int $chunkSize
    ) {
        if ($this->chunkSize <= 0) {
            throw new \InvalidArgumentException('chunkSize must be greater than 0.');
        }
        if ($this->chunkSize > 104857600) {
            throw new \InvalidArgumentException('chunkSize must be less than or equal to 100MB.');
        }
    }

    public function jsonSerialize(): mixed
    {
        return [
            'chunkSize' => $this->chunkSize,
        ];
    }
}
