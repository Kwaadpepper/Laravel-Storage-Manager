<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Query\Init;

use Kwaadpepper\LaravelStorageManager\Contract\QueryInput;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;

final readonly class InitFileManagerInput implements QueryInput
{
    public function getDisk(): ?Disk
    {
        return null;
    }

    public function getPath(): ?Path
    {
        return null;
    }
}
