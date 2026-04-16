<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path;

interface PathProperties
{
    public Path $path { get; }

    public string $basename { get; }

    public string $dirname { get; }

    public \DateTimeInterface $timestamp { get; }

    public PathVisibility $visibility { get; }

    public bool $isFile { get; }

    public bool $isDirectory { get; }
}
