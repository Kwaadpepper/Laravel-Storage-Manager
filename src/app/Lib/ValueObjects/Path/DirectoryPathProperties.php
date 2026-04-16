<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path;

readonly class DirectoryPathProperties implements PathProperties
{
    public bool $isDirectory;

    public bool $isFile;

    public function __construct(
        public Path $path,
        public string $basename,
        public string $dirname,
        public \DateTimeInterface $timestamp,
        public PathVisibility $visibility
    ) {
        $this->isFile      = false;
        $this->isDirectory = true;
    }
}
