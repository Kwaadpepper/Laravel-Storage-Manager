<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects;

final class Disk
{
    public function __construct(
        public readonly string $driver,
        public readonly string $name,
        public readonly bool $throw,
        public readonly bool $report
    ) {
    }
}
