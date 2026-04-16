<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path;

readonly class FilePathProperties implements PathProperties
{
    public bool $isDirectory;

    public bool $isFile;

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
        $this->isFile      = true;
        $this->isDirectory = false;
    }
}
