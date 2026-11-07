<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path;

use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;

final readonly class ResolvedFile
{
    public function __construct(
        public Disk $disk,
        public Path $path,
        public FilePathProperties $properties,
    ) {
    }
}
