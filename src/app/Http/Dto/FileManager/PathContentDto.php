<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Dto\FileManager;

use Kwaadpepper\LaravelStorageManager\Http\Dto\Dto;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
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
            'files'       => array_map(fn (Path $file): string => (string) $file, $this->pathList->files),
            'directories' => array_map(fn (Path $dir): string => (string) $dir, $this->pathList->directories),
        ];
    }
}
