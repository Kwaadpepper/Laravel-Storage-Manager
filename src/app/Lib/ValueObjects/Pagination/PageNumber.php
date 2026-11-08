<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Pagination;

use InvalidArgumentException;
use JsonSerializable;
use Kwaadpepper\LaravelStorageManager\Contract\Equatable;
use Stringable;

final readonly class PageNumber implements Equatable, JsonSerializable, Stringable
{
    public const int DEFAULT_PAGE = 1;

    public function __construct(
        public int $value = self::DEFAULT_PAGE,
    ) {
        if ($this->value <= 0) {
            throw new InvalidArgumentException('Page number must be greater than 0.');
        }
    }

    public static function of(int $value): self
    {
        return new self($value);
    }

    public static function default(): self
    {
        return new self(self::DEFAULT_PAGE);
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
