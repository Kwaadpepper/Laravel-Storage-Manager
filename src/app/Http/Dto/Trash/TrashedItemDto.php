<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Dto\Trash;

use Kwaadpepper\LaravelStorageManager\Http\Dto\Dto;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Trash\TrashedItem;

final readonly class TrashedItemDto implements Dto
{
    public function __construct(
        public string $id,
        public string $name,
        public string $originalPath,
        public string $type,
        public int $size,
        public string $deletedAt,
        public string $disk,
        public string $status = 'trashed',
    ) {
    }

    public static function fromValueObject(TrashedItem $item): self
    {
        return new self(
            id: $item->trashId,
            name: $item->name,
            originalPath: $item->originalPath->value,
            type: $item->type,
            size: $item->size,
            deletedAt: $item->deletedAt->toIso8601String(),
            disk: $item->diskName,
            status: $item->status,
        );
    }

    /**
     * @return array{id: string, name: string, originalPath: string, type: string, size: int, deletedAt: string, disk: string, status: string}
     */
    public function jsonSerialize(): mixed
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'originalPath' => $this->originalPath,
            'type'         => $this->type,
            'size'         => $this->size,
            'deletedAt'    => $this->deletedAt,
            'disk'         => $this->disk,
            'status'       => $this->status,
        ];
    }
}
