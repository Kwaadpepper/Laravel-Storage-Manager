<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Pagination;

use Kwaadpepper\LaravelStorageManager\Contract\Equatable;
use Stringable;

final readonly class Pagination implements Equatable, Stringable
{
    private function __construct(
        private int $from,
        private int $to,
    ) {
    }

    public function from(): int
    {
        return $this->from;
    }

    public function to(): int
    {
        return $this->to;
    }

    public function limit(): int
    {
        return $this->to - $this->from + 1;
    }

    public function offset(): int
    {
        return $this->from;
    }

    public function pageSize(): PageSize
    {
        return new PageSize($this->limit());
    }

    public function pageNumber(): PageNumber
    {
        return new PageNumber((int) ($this->from / $this->pageSize()->value()) + 1);
    }

    public function equals(mixed $other): bool
    {
        return $other instanceof self && $this->from === $other->from && $this->to === $other->to;
    }

    public static function ofPage(PageNumber $pageNumber, ?PageSize $pageSize = null): self
    {
        $size      = $pageSize ?? new PageSize();
        $fromIndex = ($pageNumber->value - 1) * $size->value;
        $toIndex   = $fromIndex + $size->value - 1;

        return new self($fromIndex, $toIndex);
    }

    public static function ofOffsetAndLimit(int $offset, int $limit): self
    {
        $offset = max(0, $offset);
        $limit  = max(1, min(PageSize::MAX_PAGE_SIZE, $limit));

        $fromIndex = $offset;
        $toIndex   = $offset + $limit - 1;

        return new self($fromIndex, $toIndex);
    }

    public static function firstPage(?PageSize $pageSize = null): self
    {
        return self::ofPage(new PageNumber(1), $pageSize);
    }

    public function __toString(): string
    {
        return sprintf('Pagination{from=%d, to=%d}', $this->from, $this->to);
    }
}
