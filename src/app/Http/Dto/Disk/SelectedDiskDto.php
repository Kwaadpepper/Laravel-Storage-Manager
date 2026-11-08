<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Dto\Disk;

use Kwaadpepper\LaravelStorageManager\Http\Dto\Dto;

final readonly class SelectedDiskDto implements Dto
{
    /**
     * @param  list<string>|null  $allowedExtensions
     */
    public function __construct(
        public string $driver,
        public string $name,
        public bool $throw,
        public bool $report,
        public bool $readOnly = false,
        public bool $trash = true,
        public ?array $allowedExtensions = null,
        public ?int $maxFileSize = null,
        public string $filenamePolicy = 'strict',
        public ?string $duplicatePolicy = null,
    ) {
    }

    public function jsonSerialize(): mixed
    {
        return [
            'disk' => [
                'driver'            => $this->driver,
                'name'              => $this->name,
                'throw'             => $this->throw,
                'report'            => $this->report,
                'readOnly'          => $this->readOnly,
                'trash'             => $this->trash,
                'allowedExtensions' => $this->allowedExtensions,
                'maxFileSize'       => $this->maxFileSize,
                'filenamePolicy'    => $this->filenamePolicy,
                'duplicatePolicy'   => $this->duplicatePolicy,
            ],
        ];
    }
}
