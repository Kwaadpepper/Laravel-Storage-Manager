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
    public function toArray(): array
    {
        return $this->jsonSerialize();
    }

    /**
     * @param  array<mixed, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            disk: self::extractString($data['disk'] ?? null),
            driver: self::extractString($data['driver'] ?? null),
            supported: ! empty($data['supported']),
            total: self::extractInt($data['total'] ?? null),
            free: self::extractInt($data['free'] ?? null),
            used: self::extractInt($data['used'] ?? null),
            percentage: self::extractFloat($data['percentage'] ?? null),
            mode: self::extractString($data['mode'] ?? null, 'disabled'),
        );
    }

    private static function extractInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private static function extractFloat(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private static function extractString(mixed $value, string $default = ''): string
    {
        return is_scalar($value) ? (string) $value : $default;
    }
}
