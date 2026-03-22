<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path;

use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path;

class DirectoryPathProperties implements PathProperties
{
    public function __construct(
        public readonly Path $path,
        public readonly string $basename,
        public readonly string $dirname,
        public readonly \DateTimeInterface $timestamp,
        public readonly PathVisibility $visibility
    ) {
    }
}
