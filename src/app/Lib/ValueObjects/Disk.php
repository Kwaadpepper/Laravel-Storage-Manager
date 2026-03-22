<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

final class Disk
{
    public function __construct(
        public readonly string $driver,
        public readonly string $name,
        public readonly bool $throw,
        public readonly bool $report
    ) {
        if (empty($this->driver)) {
            throw new \DomainException('Disk driver cannot be empty.');
        }
        if (empty($this->name)) {
            throw new \DomainException('Disk name cannot be empty.');
        }
    }

    public function getStorageDisk(): Filesystem
    {
        return Storage::disk($this->name);
    }
}
