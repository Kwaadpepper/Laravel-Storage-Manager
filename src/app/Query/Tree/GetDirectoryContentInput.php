<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Query\Tree;

use Kwaadpepper\LaravelStorageManager\Contract\QueryInput;
use Kwaadpepper\LaravelStorageManager\Enum\FileSortField;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Pagination\Pagination;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use SortDirection;

final readonly class GetDirectoryContentInput implements QueryInput
{
    public Pagination $pagination;

    public function __construct(
        public Disk $disk,
        public ?Path $path = null,
        ?Pagination $pagination = null,
        public ?FileSortField $sortBy = null,
        public SortDirection $sortDirection = SortDirection::Ascending,
    ) {
        $this->pagination = $pagination ?? Pagination::firstPage();
    }

    public function getDisk(): Disk
    {
        return $this->disk;
    }

    public function getPath(): ?Path
    {
        return $this->path;
    }

    public function getPagination(): Pagination
    {
        return $this->pagination;
    }

    public function getSortBy(): ?FileSortField
    {
        return $this->sortBy;
    }

    public function getSortDirection(): SortDirection
    {
        return $this->sortDirection;
    }
}
