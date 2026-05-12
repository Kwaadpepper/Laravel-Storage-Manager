<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree;

class PathTreeLevel
{
    /**
     * @param  list<PathTreeDirectory>  $directories
     * @param  list<PathTreeFile>  $files
     */
    public function __construct(
        public readonly array $directories,
        public readonly array $files,
    ) {
        foreach ($directories as $directory) {
            if (! $directory instanceof PathTreeDirectory) {
                throw new \DomainException('All items in directories must be instances of PathTreeDirectory.');
            }
        }
        foreach ($files as $file) {
            if (! $file instanceof PathTreeFile) {
                throw new \DomainException('All items in files must be instances of PathTreeFile.');
            }
        }
    }
}
