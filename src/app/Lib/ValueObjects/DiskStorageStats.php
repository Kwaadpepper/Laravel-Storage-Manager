<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects;

final readonly class DiskStorageStats
{
    public function __construct(
        public string $mode,
        public ?int $total = null,
        public ?int $free = null,
        public ?int $used = null,
        public ?float $percentage = null,
    ) {
    }
}
