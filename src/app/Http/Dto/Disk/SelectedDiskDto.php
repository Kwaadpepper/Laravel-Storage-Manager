<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Dto\Disk;

use Kwaadpepper\LaravelStorageManager\Http\Dto\Dto;

final readonly class SelectedDiskDto implements Dto
{
    public function __construct(
        public string $driver,
        public string $name,
        public bool $throw,
        public bool $report,
    ) {
    }

    public function jsonSerialize(): mixed
    {
        return [
            'disk' => [
                'driver' => $this->driver,
                'name'   => $this->name,
                'throw'  => $this->throw,
                'report' => $this->report,
            ],
        ];
    }
}
