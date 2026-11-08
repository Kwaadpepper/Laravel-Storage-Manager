<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Pagination;

use InvalidArgumentException;
use JsonSerializable;
use Kwaadpepper\LaravelStorageManager\Contract\Equatable;
use Stringable;

final readonly class PageSize implements Equatable, JsonSerializable, Stringable
{
    public const int DEFAULT_SIZE = 50;

    public const int MAX_PAGE_SIZE = 200;

    public function __construct(
        public int $value = self::DEFAULT_SIZE,
    ) {
        if ($this->value <= 0) {
            throw new InvalidArgumentException('Page size must be greater than 0.');
        }

        if ($this->value > self::MAX_PAGE_SIZE) {
            throw new InvalidArgumentException('Page size must be less than or equal to ' . self::MAX_PAGE_SIZE . '.');
        }
    }

    public static function of(int $value): self
    {
        return new self($value);
    }

    public static function default(): self
    {
        return new self(self::DEFAULT_SIZE);
    }

    public function value(): int
    {
        return $this->value;
    }

    public function equals(mixed $other): bool
    {
        return $other instanceof self && $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }

    public function jsonSerialize(): int
    {
        return $this->value;
    }
}
