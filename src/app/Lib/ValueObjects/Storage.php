<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects;

use Kwaadpepper\LaravelStorageManager\Exception\IllegalDomainStateException;

final class Storage
{
    public function __construct(
        public readonly string $name,
        public readonly string $disk
    ) {
        if (empty($this->name)) {
            throw new IllegalDomainStateException('Storage name cannot be empty.');
        }
        if (empty($this->disk)) {
            throw new IllegalDomainStateException('Storage disk cannot be empty.');
        }
    }
}
