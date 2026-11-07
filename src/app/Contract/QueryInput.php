<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Contract;

use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;

interface QueryInput
{
    public function getDisk(): ?Disk;

    public function getPath(): ?Path;
}
