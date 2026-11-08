<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path;

use Kwaadpepper\LaravelStorageManager\Contract\Equatable;
use Kwaadpepper\LaravelStorageManager\Exception\IllegalDomainStateException;

class Path implements \Stringable, Equatable
{
    public function __construct(
        public readonly string $value
    ) {
        if (! str_starts_with($this->value, '/') || empty(mb_trim($this->value))) {
            throw new IllegalDomainStateException('Path must start with a \'/\'.');
        }

        if (str_contains($this->value, "\0")) {
            throw new IllegalDomainStateException('Path must not contain null bytes.');
        }

        $segments = preg_split('/[\/\\\\]+/', $this->value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($segments as $segment) {
            if ($segment === '..') {
                throw new IllegalDomainStateException("Path must not contain '..' segments.");
            }
        }
    }

    public static function root(): self
    {
        return new self('/');
    }

    public static function appendTo(Path $basePath, string $append): self
    {
        $normalizedAppend = ltrim($append, '/');
        $newPath          = rtrim((string) $basePath, '/') . '/' . $normalizedAppend;

        return new self($newPath);
    }

    public function isRoot(): bool
    {
        return $this->value === '/';
    }

    /**
     * Returns the Flysystem-compatible relative path (no leading slash).
     */
    public function toRelative(): string
    {
        return ltrim($this->value, '/');
    }

    /**
     * Returns the parent directory as a Path, or root if already at root.
     */
    public function parent(): self
    {
        $dir = dirname($this->value);

        return new self($dir === '.' || $dir === '\\' ? '/' : $dir);
    }

    public function basename(): string
    {
        return basename($this->value);
    }

    public function filename(): string
    {
        return pathinfo($this->value, PATHINFO_FILENAME);
    }

    public function extension(): string
    {
        return strtolower(pathinfo($this->value, PATHINFO_EXTENSION));
    }

    public function equals(mixed $other): bool
    {
        return $other instanceof self && $this->value === $other->value;
    }

    /**
     * Determine whether this path starts with the given path prefix, respecting segment boundaries.
     */
    public function startsWith(self $prefix): bool
    {
        if ($prefix->isRoot()) {
            return true;
        }

        if ($this->value === $prefix->value) {
            return true;
        }

        return str_starts_with($this->value, rtrim($prefix->value, '/') . '/');
    }

    /**
     * Returns the relative path segment from the given base path (without leading slash).
     *
     * @throws \InvalidArgumentException When this path does not start with the given base path.
     */
    public function relativeTo(self $basePath): string
    {
        if (! $this->startsWith($basePath)) {
            throw new \InvalidArgumentException("Path '{$this->value}' does not start with base path '{$basePath->value}'.");
        }

        if ($this->equals($basePath)) {
            return '';
        }

        if ($basePath->isRoot()) {
            return ltrim($this->value, '/');
        }

        $prefix = rtrim($basePath->value, '/') . '/';

        return substr($this->value, strlen($prefix));
    }

    /**
     * Rebases this path from a source base directory to a target base directory.
     *
     * @throws \InvalidArgumentException When this path is not within the source base path.
     */
    public function rebase(self $sourceBase, self $targetBase): self
    {
        $relative = $this->relativeTo($sourceBase);

        if ($relative === '') {
            return $targetBase;
        }

        return self::appendTo($targetBase, $relative);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
