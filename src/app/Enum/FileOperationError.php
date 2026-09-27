<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Enum;

use Kwaadpepper\LaravelStorageManager\Exception\DomainErrorCode;

enum FileOperationError: int implements DomainErrorCode
{
    case DIRECTORY_ALREADY_EXISTS     = 1001;
    case DIRECTORY_NOT_FOUND          = 1002;
    case FILE_ALREADY_EXISTS          = 1003;
    case FILE_NOT_FOUND               = 1004;
    case INVALID_PATH                 = 1005;
    case PERMISSION_DENIED            = 1006;
    case UNKNOWN_ERROR                = 1007;
    case DIRECTORY_NOT_EMPTY          = 1008;
    case READ_ONLY_DISK               = 1009;
    case ARCHIVE_OPERATION_FAILED     = 1010;
    case INVALID_ARCHIVE              = 1011;
    case ZIP_EXTENSION_NOT_AVAILABLE  = 1012;

    /** @var array<int, string> */
    private const array MESSAGES = [
        1001 => 'The directory already exists.',
        1002 => 'The directory was not found.',
        1003 => 'The file already exists.',
        1004 => 'The file was not found.',
        1005 => 'The provided path is invalid.',
        1006 => 'Permission denied for the requested operation.',
        1007 => 'An unknown error occurred during the file operation.',
        1008 => 'The directory is not empty.',
        1009 => 'The operation is not allowed on a read-only disk.',
        1010 => 'Failed to create or extract archive.',
        1011 => 'The archive file is invalid or corrupted.',
        1012 => 'The PHP zip extension is not installed.',
    ];

    public function message(): string
    {
        return self::MESSAGES[$this->value] ?? '';
    }

    public function code(): int
    {
        return $this->value;
    }
}
