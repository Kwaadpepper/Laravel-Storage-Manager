<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Query\Disk;

use Kwaadpepper\LaravelStorageManager\Contract\QueryInput;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;

final readonly class GetDiskStatsInput implements QueryInput
{
    public function __construct(
        public Disk $disk,
        public bool $fresh = false,
    ) {
    }

    public function getDisk(): Disk
    {
        return $this->disk;
    }

    public function getPath(): ?Path
    {
        return null;
    }
}
