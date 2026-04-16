<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Dto\FileManager;

use Kwaadpepper\LaravelStorageManager\Http\Dto\Dto;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathList;

final readonly class PathContentDto implements Dto
{
    public function __construct(
        public PathList $pathList
    ) {
    }

    public function jsonSerialize(): mixed
    {
        return [
            'files'       => array_map(fn ($file) => (string) $file, $this->pathList->files),
            'directories' => array_map(fn ($dir) => (string) $dir, $this->pathList->directories),
        ];
    }
}
