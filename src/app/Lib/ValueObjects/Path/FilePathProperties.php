<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path;

use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path;

class FilePathProperties implements PathProperties
{
    public function __construct(
        public readonly Path $path,
        public readonly string $basename,
        public readonly string $dirname,
        public readonly string $extension,
        public readonly string $filename,
        public readonly int $size,
        public readonly \DateTimeInterface $timestamp,
        public readonly PathVisibility $visibility
    ) {
    }
}
