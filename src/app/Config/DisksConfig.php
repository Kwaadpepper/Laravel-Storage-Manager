<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Config;

use Illuminate\Support\Arr;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;

final readonly class DisksConfig
{
    /**
     * @var array<string, array<string, scalar|null>>
     */
    public array $filesystemsDisks;

    /**
     * @param  list<string>  $available
     * @param  array<string, array<string, scalar|null>>  $filesystemsDisks
     * @param  array<string, Disk>  $disks
     */
    public function __construct(
        public array $available,
        public ?string $default,
        array $filesystemsDisks = [],
        public array $disks = [],
    ) {
        $validated = [];
        foreach ($filesystemsDisks as $diskName => $options) {
            $validatedOptions = [];
            foreach ($options as $optKey => $optVal) {
                if (is_scalar($optVal) || $optVal === null) {
                    $validatedOptions[$optKey] = $optVal;
                }
            }
            $validated[$diskName] = $validatedOptions;
        }
        $this->filesystemsDisks = $validated;
    }

    public function resolveDriver(string $disk, ?string $driver = null): string
    {
        $fsConfig    = $this->filesystemsDisks;
        $currentDisk = $disk;

        if ($driver === null) {
            $rawDriver = Arr::get($fsConfig, "{$currentDisk}.driver");
            $driver    = is_string($rawDriver) ? $rawDriver : '';
        }

        $visited = [$currentDisk];

        while ($driver === 'scoped') {
            $parentDisk = Arr::get($fsConfig, "{$currentDisk}.disk");

            if (! is_string($parentDisk) || $parentDisk === '') {
                throw new \UnexpectedValueException("Scoped disk '{$currentDisk}' is missing a valid parent 'disk' configuration.");
            }

            if (in_array($parentDisk, $visited, true)) {
                throw new \UnexpectedValueException("Circular reference detected in scoped disk '{$currentDisk}'.");
            }

            $parentDriver = Arr::get($fsConfig, "{$parentDisk}.driver");

            if (! is_string($parentDriver) || $parentDriver === '') {
                throw new \UnexpectedValueException("Parent disk '{$parentDisk}' of scoped disk '{$currentDisk}' has no valid driver configured.");
            }

            $visited[]   = $parentDisk;
            $driver      = $parentDriver;
            $currentDisk = $parentDisk;
        }

        return $driver;
    }

    public function getDefaultDisk(): ?Disk
    {
        $defaultDiskName = $this->default ?? array_key_first($this->disks);

        if ($defaultDiskName === null || empty(mb_trim($defaultDiskName))) {
            return null;
        }

        return $this->disks[$defaultDiskName] ?? array_values($this->disks)[0] ?? null;
    }

    public function getDisk(string $diskName): ?Disk
    {
        return $this->disks[$diskName] ?? null;
    }

    /**
     * @return array<string, Disk>
     */
    public function getDisksMap(): array
    {
        return $this->disks;
    }
}
