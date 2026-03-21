<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects;

class PathList
{
    /**
     * @param  list<Path>  $paths
     */
    public function __construct(
        public readonly array $files,
        public readonly array $directories,
    ) {
        foreach ($files as $file) {
            if (! $file instanceof Path) {
                throw new \DomainException('All items in files must be instances of Path.');
            }
        }

        foreach ($directories as $directory) {
            if (! $directory instanceof Path) {
                throw new \DomainException('All items in directories must be instances of Path.');
            }
        }
    }
}
