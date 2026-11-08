<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Exception;

use Illuminate\Contracts\Debug\ShouldntReport;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;

class OperationNotSupportedException extends FileOperationException implements ShouldntReport
{
    public static function throwWith(
        FileOperationError $error = FileOperationError::OPERATION_NOT_SUPPORTED,
        ?\Throwable $previous = null,
    ): never {
        throw new self($error, $previous);
    }
}
