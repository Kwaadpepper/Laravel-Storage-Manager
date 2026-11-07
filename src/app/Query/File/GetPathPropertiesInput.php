<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Query\File;

use Kwaadpepper\LaravelStorageManager\Contract\QueryInput;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;

final readonly class GetPathPropertiesInput implements QueryInput
{
    public function __construct(
        public Disk $disk,
        public Path $path,
    ) {
    }

    public function getDisk(): Disk
    {
        return $this->disk;
    }

    public function getPath(): Path
    {
        return $this->path;
    }
}
