<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Exception;

use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;

class OperationCancelledException extends FileOperationException
{
    public function __construct(
        string $reason = '',
        ?\Throwable $previous = null,
    ) {
        parent::__construct(FileOperationError::OPERATION_CANCELLED, $previous);

        if ($reason !== '') {
            $this->message = $reason;
        }
    }

    public static function throwWithReason(string $reason, ?\Throwable $previous = null): never
    {
        throw new self($reason, $previous);
    }
}
