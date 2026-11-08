<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree;

use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;

final readonly class DirectoryListingResult
{
    /**
     * @param  list<DirectoryAttributes>  $directories
     * @param  list<FileAttributes>  $files
     */
    public function __construct(
        public array $directories,
        public array $files,
    ) {
    }
}
