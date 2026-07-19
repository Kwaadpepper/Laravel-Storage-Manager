<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Dto\FileManager;

use Kwaadpepper\LaravelStorageManager\Http\Dto\Dto;

final readonly class InitDto implements Dto
{
    public function __construct(
        public int $chunkMinSize,
        public int $chunkMaxSize
    ) {
        if ($this->chunkMinSize <= 0) {
            throw new \InvalidArgumentException('chunkMinSize must be greater than 0.');
        }
        if ($this->chunkMaxSize > 104857600) {
            throw new \InvalidArgumentException('chunkMaxSize must be less than or equal to 100MB.');
        }
        if ($this->chunkMinSize > $this->chunkMaxSize) {
            throw new \InvalidArgumentException('chunkMinSize must be less than or equal to chunkMaxSize.');
        }
    }

    public function jsonSerialize(): mixed
    {
        return [
            'chunkMinSize' => $this->chunkMinSize,
            'chunkMaxSize' => $this->chunkMaxSize,
        ];
    }
}
