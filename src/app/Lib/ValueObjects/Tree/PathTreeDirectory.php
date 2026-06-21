<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree;

use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathVisibility;

class PathTreeDirectory
{
    public function __construct(
        public readonly Path $path,
        public readonly bool $hasSubDirectories,
        public readonly PathVisibility $visibility
    ) {
    }
}
