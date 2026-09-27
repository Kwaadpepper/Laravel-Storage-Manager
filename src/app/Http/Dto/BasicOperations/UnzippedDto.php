<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Dto\BasicOperations;

use Kwaadpepper\LaravelStorageManager\Http\Dto\Dto;

final readonly class UnzippedDto implements Dto
{
    public function __construct(
        public string $destination = ''
    ) {
    }

    /**
     * @return array{destination: string}
     */
    public function jsonSerialize(): mixed
    {
        return [
            'destination' => $this->destination,
        ];
    }
}
