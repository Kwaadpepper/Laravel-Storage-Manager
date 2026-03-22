<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects;

final class Storage
{
    public function __construct(
        public readonly string $name,
        public readonly string $disk
    ) {
        $this->name = $name;
        $this->disk = $disk;

        if (empty($this->name)) {
            throw new \DomainException('Storage name cannot be empty.');
        }
        if (empty($this->disk)) {
            throw new \DomainException('Storage disk cannot be empty.');
        }
    }
}
