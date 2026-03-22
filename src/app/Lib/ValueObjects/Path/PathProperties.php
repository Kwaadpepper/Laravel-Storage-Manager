<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path;

interface PathProperties
{
    public readonly Path $path;

    public readonly string $basename;

    public readonly string $dirname;

    public readonly \DateTimeInterface $timestamp;

    public readonly PathVisibility $visibility;
}
