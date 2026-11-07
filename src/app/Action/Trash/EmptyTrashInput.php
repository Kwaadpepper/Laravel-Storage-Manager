<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Action\Trash;

use Kwaadpepper\LaravelStorageManager\Contract\ActionInput;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;

final readonly class EmptyTrashInput implements ActionInput
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
