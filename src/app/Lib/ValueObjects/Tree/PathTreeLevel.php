<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree;

class PathTreeLevel
{
    /**
     * @param  list<PathTreeDirectory>  $directories
     */
    public function __construct(
        public readonly array $directories,
    ) {
        foreach ($directories as $directory) {
            if (! $directory instanceof PathTreeDirectory) {
                throw new \DomainException('All items in directories must be instances of PathTreeDirectory.');
            }
        }
    }
}
