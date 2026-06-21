<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload;

use Illuminate\Support\Str;
use Kwaadpepper\LaravelStorageManager\Exception\IllegalDomainStateException;
use Ramsey\Uuid\Uuid;

final readonly class UploadId implements \Stringable
{
    public function __construct(
        public string $value
    ) {
        if (! Str::isUuid($this->value, Uuid::UUID_TYPE_UNIX_TIME)) {
            throw new IllegalDomainStateException('UploadId must be a valid UUID.');
        }
    }

    public static function generate(): self
    {
        return new self(Uuid::uuid7()->toString());
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
