<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Enum;

enum FileOperationError: string
{
    case DIRECTORY_ALREADY_EXISTS = 'directory_already_exists';
    case DIRECTORY_NOT_FOUND      = 'directory_not_found';
    case FILE_ALREADY_EXISTS      = 'file_already_exists';
    case FILE_NOT_FOUND           = 'file_not_found';
    case INVALID_PATH             = 'invalid_path';
    case PERMISSION_DENIED        = 'permission_denied';
    case UNKNOWN_ERROR            = 'unknown_error';

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
        };
    }

    public function httpStatusCode(): int
    {
        return match ($this) {
            self::DIRECTORY_ALREADY_EXISTS => 1,
            self::DIRECTORY_NOT_FOUND      => 2,
            self::FILE_ALREADY_EXISTS      => 3,
            self::FILE_NOT_FOUND           => 4,
            self::INVALID_PATH             => 5,
            self::PERMISSION_DENIED        => 6,
            self::UNKNOWN_ERROR            => 7,
        };
    }
}
