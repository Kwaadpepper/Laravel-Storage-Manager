<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Query\Disk;

use Kwaadpepper\LaravelStorageManager\Contract\QueryInput;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;

final readonly class GetConfiguredDisksInput implements QueryInput
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
