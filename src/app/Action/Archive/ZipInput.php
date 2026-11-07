<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Action\Archive;

use Kwaadpepper\LaravelStorageManager\Contract\ActionInput;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;

final readonly class ZipInput implements ActionInput
{
    /**
     * @param  Path[]  $paths
     */
    public function __construct(
        public Disk $disk,
        public array $paths,
        public Path $destination,
    ) {
    }

    public function getDisk(): Disk
    {
        return $this->disk;
    }

    public function getPath(): Path
    {
        return $this->destination;
    }
}
