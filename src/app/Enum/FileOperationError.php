<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Enum;

use Kwaadpepper\LaravelStorageManager\Exception\DomainErrorCode;

enum FileOperationError: int implements DomainErrorCode
{
    case DIRECTORY_ALREADY_EXISTS = 1001;
    case DIRECTORY_NOT_FOUND      = 1002;
    case FILE_ALREADY_EXISTS      = 1003;
    case FILE_NOT_FOUND           = 1004;
    case INVALID_PATH             = 1005;
    case PERMISSION_DENIED        = 1006;
    case UNKNOWN_ERROR            = 1007;
    case DIRECTORY_NOT_EMPTY      = 1008;

    public function message(): string
    {
        return match ($this) {
            self::DIRECTORY_ALREADY_EXISTS => 'The directory already exists.',
            self::DIRECTORY_NOT_FOUND      => 'The directory was not found.',
            self::FILE_ALREADY_EXISTS      => 'The file already exists.',
            self::FILE_NOT_FOUND           => 'The file was not found.',
            self::INVALID_PATH             => 'The provided path is invalid.',
            self::PERMISSION_DENIED        => 'Permission denied for the requested operation.',
            self::UNKNOWN_ERROR            => 'An unknown error occurred during the file operation.',
            self::DIRECTORY_NOT_EMPTY      => 'The directory is not empty.',
        };
    }

    public function code(): int
    {
        return $this->value;
    }
}
