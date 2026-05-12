<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Dto\FileManager;

use Kwaadpepper\LaravelStorageManager\Http\Dto\Dto;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeDirectory;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeFile;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeLevel;

final readonly class PathTreeLevelDto implements Dto
{
    public function __construct(
        public PathTreeLevel $fileTree,
    ) {
    }

    public function jsonSerialize(): mixed
    {
        return [
            'directories' => array_map(fn (PathTreeDirectory $dir) => [
                'path'              => $dir->path->value,
                'hasSubDirectories' => $dir->hasSubDirectories,
            ], $this->fileTree->directories),
            'files'       => array_map(fn (PathTreeFile $file) => [
                'path'      => $file->path->value,
                'size'      => $file->size,
                'extension' => $file->extension,
            ], $this->fileTree->files),
        ];
    }
}
