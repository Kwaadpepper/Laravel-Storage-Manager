<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path;

use Kwaadpepper\LaravelStorageManager\Exception\IllegalDomainStateException;

class PathList
{
    /**
     * @param  list<Path>  $files
     * @param  list<Path>  $directories
     */
    public function __construct(
        public readonly array $files,
        public readonly array $directories,
    ) {
        foreach ($files as $path) {
            if (! $path instanceof Path) {
                throw new IllegalDomainStateException('All items in files must be instances of Path.');
            }
        }

        foreach ($directories as $directory) {
            if (! $directory instanceof Path) {
                throw new IllegalDomainStateException('All items in directories must be instances of Path.');
            }
        }
    }
}
