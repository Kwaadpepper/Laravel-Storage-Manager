<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Dto\Disk;

use Kwaadpepper\LaravelStorageManager\Exception\DomainException;
use Kwaadpepper\LaravelStorageManager\Http\Dto\Dto;

final readonly class DiskListDto implements Dto
{
    /**
     * @param  list<string>  $diskNames
     */
    public function __construct(
        public array $diskNames,
    ) {
        foreach ($diskNames as $diskName) {
            if (! is_string($diskName)) {
                throw new DomainException('All disk names must be strings.');
            }
        }
    }

    public function jsonSerialize(): mixed
    {
        return [
            'disks' => $this->diskNames,
        ];
    }
}
