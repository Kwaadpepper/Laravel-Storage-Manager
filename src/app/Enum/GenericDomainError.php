<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Enum;

use Kwaadpepper\LaravelStorageManager\Exception\DomainErrorCode;

enum GenericDomainError: int implements DomainErrorCode
{
    case DISK_NOT_FOUND          = 2001;
    case INVALID_DISK_LIST       = 2002;
    case INVALID_FILE_PROPERTIES = 2003;

    public function code(): int
    {
        return $this->value;
    }

    public function message(): string
    {
        return match ($this) {
            self::DISK_NOT_FOUND          => 'The configured disk does not exist.',
            self::INVALID_DISK_LIST       => 'All disk names must be strings.',
            self::INVALID_FILE_PROPERTIES => 'For files, extension and size must be provided.',
        };
    }
}
