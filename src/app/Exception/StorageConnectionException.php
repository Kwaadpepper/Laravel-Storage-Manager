<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Exception;

use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;

class StorageConnectionException extends FileOperationException
{
    public static function throwWith(
        FileOperationError $error = FileOperationError::CONNECTION_ERROR,
        ?\Throwable $previous = null,
    ): never {
        throw new self($error, $previous);
    }
}
