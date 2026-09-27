<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Dto\BasicOperations;

use Kwaadpepper\LaravelStorageManager\Http\Dto\Dto;

final readonly class ZippedDto implements Dto
{
    public function __construct(
        public string $path = ''
    ) {
    }

    /**
     * @return array{path: string}
     */
    public function jsonSerialize(): mixed
    {
        return [
            'path' => $this->path,
        ];
    }
}
