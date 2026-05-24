<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Exception;

use Illuminate\Contracts\Debug\ShouldntReport;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;

/** @extends DomainException<FileOperationError> */
class FileOperationException extends DomainException implements ShouldntReport
{
    public static function throwWith(FileOperationError $error): never
    {
        throw new self($error);
    }
}
