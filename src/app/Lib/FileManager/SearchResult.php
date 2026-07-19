<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\FileManager;

use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeDirectory;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeFile;

class SearchResult
{
    /**
     * @param  Path[]  $directoriesToScan
     * @param  PathTreeDirectory[]  $matchedDirectories
     * @param  PathTreeFile[]  $matchedFiles
     */
    public function __construct(
        public readonly array $directoriesToScan,
        public readonly array $matchedDirectories,
        public readonly array $matchedFiles
    ) {
    }
}
