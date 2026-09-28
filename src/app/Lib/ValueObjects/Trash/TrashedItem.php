<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Trash;

use Carbon\CarbonImmutable;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;

final readonly class TrashedItem
{
    public function __construct(
        public string $trashId,
        public Path $originalPath,
        public string $name,
        public string $type,
        public int $size,
        public CarbonImmutable $deletedAt,
        public string $diskName,
        public string $status = 'trashed',
    ) {
    }

    /**
     * @return array{id: string, originalPath: string, name: string, type: string, size: int, deletedAt: string, disk: string, status: string}
     */
    public function toArray(): array
    {
        return [
            'id'           => $this->trashId,
            'originalPath' => $this->originalPath->value,
            'name'         => $this->name,
            'type'         => $this->type,
            'size'         => $this->size,
            'deletedAt'    => $this->deletedAt->toIso8601String(),
            'disk'         => $this->diskName,
            'status'       => $this->status,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            trashId: self::extractString($data, 'id'),
            originalPath: new Path(self::extractString($data, 'originalPath')),
            name: self::extractString($data, 'name'),
            type: self::extractString($data, 'type', 'file'),
            size: self::extractInt($data, 'size'),
            deletedAt: CarbonImmutable::parse(self::extractString($data, 'deletedAt', 'now')),
            diskName: self::extractString($data, 'disk'),
            status: self::extractString($data, 'status', 'trashed'),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function extractString(array $data, string $key, string $default = ''): string
    {
        $val = $data[$key] ?? $default;

        return is_string($val) ? $val : $default;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function extractInt(array $data, string $key, int $default = 0): int
    {
        $val = $data[$key] ?? $default;

        return is_int($val) ? $val : $default;
    }
}
