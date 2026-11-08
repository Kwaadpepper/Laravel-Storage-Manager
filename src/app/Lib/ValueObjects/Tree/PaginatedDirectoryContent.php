<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree;

use Kwaadpepper\LaravelStorageManager\Exception\IllegalDomainStateException;

final readonly class PaginatedDirectoryContent
{
    /**
     * @param  list<PathTreeDirectory>  $directories
     * @param  list<PathTreeFile>  $files
     */
    public function __construct(
        public array $directories,
        public array $files,
        public PaginationMeta $pagination,
    ) {
        foreach ($directories as $directory) {
            if (! $directory instanceof PathTreeDirectory) {
                throw new IllegalDomainStateException('All items in directories must be instances of PathTreeDirectory.');
            }
        }

        foreach ($files as $file) {
            if (! $file instanceof PathTreeFile) {
                throw new IllegalDomainStateException('All items in files must be instances of PathTreeFile.');
            }
        }
    }
}
