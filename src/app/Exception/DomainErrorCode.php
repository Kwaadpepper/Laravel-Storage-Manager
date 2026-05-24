<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Exception;

interface DomainErrorCode extends \UnitEnum
{
    public function code(): int;

    public function message(): string;
}
