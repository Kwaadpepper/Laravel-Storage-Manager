<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path;

class Path implements \Stringable
{
    public function __construct(
        private readonly string $path
    ) {
        if (
            ! str_starts_with($this->path, '/')
            || empty(mb_trim($this->path))
        ) {
            throw new \DomainException('Path must start with a \'/\'.');
        }
    }

    public function __toString(): string
    {
        return $this->path;
    }
}
