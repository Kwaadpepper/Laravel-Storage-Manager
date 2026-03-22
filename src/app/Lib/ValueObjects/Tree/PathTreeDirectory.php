<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree;

class PathTreeDirectory
{
    public function __construct(
        public readonly string $path,
        public readonly bool $hasSubDirectories
    ) {
        if (
            ! str_starts_with($this->path, '/')
            || empty(mb_trim($this->path))
        ) {
            throw new \DomainException('Path must start with a \'/\'.');
        }
    }
}
