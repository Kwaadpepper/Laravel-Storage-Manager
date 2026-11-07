<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Action\File;

use Kwaadpepper\LaravelStorageManager\Contract\ActionInput;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;

final readonly class DeletePathInput implements ActionInput
{
    public function __construct(
        public Disk $disk,
        public Path $path,
        public bool $force = false,
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
