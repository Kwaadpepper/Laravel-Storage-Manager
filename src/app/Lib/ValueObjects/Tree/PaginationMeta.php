<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree;

use Kwaadpepper\LaravelStorageManager\Exception\IllegalDomainStateException;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Pagination\Pagination;

final readonly class PaginationMeta
{
    public int $totalPages;

    public bool $hasMore;

    public function __construct(
        public int $currentPage,
        public int $perPage,
        public int $totalFiles,
        public int $totalDirectories = 0,
    ) {
        if ($this->currentPage < 1) {
            throw new IllegalDomainStateException('Current page must be at least 1.');
        }

        if ($this->perPage < 1) {
            throw new IllegalDomainStateException('Per page must be at least 1.');
        }

        if ($this->totalFiles < 0) {
            throw new IllegalDomainStateException('Total files must be non-negative.');
        }

        if ($this->totalDirectories < 0) {
            throw new IllegalDomainStateException('Total directories must be non-negative.');
        }

        $this->totalPages = $this->totalFiles === 0 ? 0 : (int) ceil($this->totalFiles / $this->perPage);
        $this->hasMore    = $this->currentPage < $this->totalPages;
    }

    public static function fromPagination(
        Pagination $pagination,
        int $totalFiles,
        int $totalDirectories = 0
    ): self {
        return new self(
            $pagination->pageNumber()->value,
            $pagination->pageSize()->value,
            $totalFiles,
            $totalDirectories
        );
    }
}
