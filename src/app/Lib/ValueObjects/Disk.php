<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Exception\IllegalDomainStateException;

final class Disk
{
    public function __construct(
        public readonly string $driver,
        public readonly string $name,
        public readonly bool $throw,
        public readonly bool $report,
        public readonly bool $readOnly = false,
    ) {
        if (empty($this->driver)) {
            throw new IllegalDomainStateException('Disk driver cannot be empty.');
        }
        if (empty($this->name)) {
            throw new IllegalDomainStateException('Disk name cannot be empty.');
        }
    }

    public function getStorageDisk(): Filesystem
    {
        return Storage::disk($this->name);
    }
}
