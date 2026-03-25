<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path;

class Path implements \Stringable
{
    public function __construct(
        public readonly string $value
    ) {
        if (
            ! str_starts_with($this->value, '/')
            || empty(mb_trim($this->value))
        ) {
            throw new \DomainException('Path must start with a \'/\'.');
        }
    }

    public static function appendTo(Path $basePath, string $append): self
    {
        $normalizedAppend = ltrim($append, '/');
        $newPath          = rtrim((string) $basePath, '/') . '/' . $normalizedAppend;

        return new self($newPath);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
