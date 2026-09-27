<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Dto\Disk;

use Kwaadpepper\LaravelStorageManager\Http\Dto\Dto;

final readonly class DiskStatsDto implements Dto
{
    public function __construct(
        public string $disk,
        public string $driver,
        public bool $supported,
        public ?int $total,
        public ?int $free,
        public ?int $used,
        public ?float $percentage,
        public string $mode,
    ) {
    }

    /**
     * @return array{
     *     disk: string,
     *     driver: string,
     *     supported: bool,
     *     total: int|null,
     *     free: int|null,
     *     used: int|null,
     *     percentage: float|null,
     *     mode: string
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'disk'       => $this->disk,
            'driver'     => $this->driver,
            'supported'  => $this->supported,
            'total'      => $this->total,
            'free'       => $this->free,
            'used'       => $this->used,
            'percentage' => $this->percentage,
            'mode'       => $this->mode,
        ];
    }
}
