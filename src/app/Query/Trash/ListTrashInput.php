<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Query\Trash;

use Kwaadpepper\LaravelStorageManager\Contract\QueryInput;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;

final readonly class ListTrashInput implements QueryInput
{
    public function __construct(
        public Disk $disk,
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
