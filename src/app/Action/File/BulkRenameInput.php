<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Action\File;

use Kwaadpepper\LaravelStorageManager\Contract\ActionInput;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;

final readonly class BulkRenameInput implements ActionInput
{
    /**
     * @param  array<int, array{from: Path, to: string}>  $renames
     */
    public function __construct(
        public Disk $disk,
        public array $renames,
    ) {
    }

    public function getDisk(): Disk
    {
        return $this->disk;
    }

    public function getPath(): ?Path
    {
        return $this->renames[0]['from'] ?? null;
    }
}
