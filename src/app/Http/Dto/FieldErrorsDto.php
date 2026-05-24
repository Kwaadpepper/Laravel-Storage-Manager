<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Dto;

final readonly class FieldErrorsDto implements Dto
{
    /** @param array<string, string> $errors */
    public function __construct(
        public array $errors,
    ) {
        foreach ($errors as $field => $message) {
            if (! is_string($field) || ! is_string($message)) {
                throw new \InvalidArgumentException('All field errors must be string key-value pairs.');
            }
        }
    }

    public function jsonSerialize(): mixed
    {
        return $this->errors;
    }
}
