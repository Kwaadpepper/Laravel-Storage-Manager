<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Dto;

final readonly class ErrorDto implements Dto
{
    public function __construct(
        public string $message,
    ) {
    }

    public function jsonSerialize(): mixed
    {
        return [
            'error' => $this->message,
        ];
    }
}
