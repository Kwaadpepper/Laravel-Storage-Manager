<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Query\Search;

use Kwaadpepper\LaravelStorageManager\Contract\QueryInput;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;

final readonly class SearchFilesInput implements QueryInput
{
    /**
     * @param  Path[]  $paths
     */
    public function __construct(
        public Disk $disk,
        public array $paths,
        public ?string $query = null,
        public ?string $extension = null,
        public ?int $minSize = null,
        public ?int $maxSize = null,
    ) {
    }

    public function getDisk(): Disk
    {
        return $this->disk;
    }

    public function getPath(): ?Path
    {
        return $this->paths[0] ?? null;
    }
}
