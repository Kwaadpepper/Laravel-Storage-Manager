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
        $this->driver = $driver;
        $this->name   = $name;
        $this->throw  = $throw;
        $this->report = $report;

        if (empty($this->driver)) {
            throw new \InvalidArgumentException('Disk driver cannot be empty.');
        }
        if (empty($this->name)) {
            throw new \InvalidArgumentException('Disk name cannot be empty.');
        }
    }
}
