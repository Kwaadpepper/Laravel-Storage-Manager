<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Dto;

final readonly class DomainErrorDto implements Dto
{
    public function __construct(
        public int $code,
        public string $message,
    ) {
    }

    public function jsonSerialize(): mixed
    {
        return [
            'code'    => $this->code,
            'message' => $this->message,
        ];
    }
}
