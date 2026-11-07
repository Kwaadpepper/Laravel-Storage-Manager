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
     */
    public function __construct(
        public array $available,
        public ?string $default,
        array $filesystemsDisks = [],
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
        $disksConfig = $this->filesystemsDisks;

        /** @var int|string|null $fallbackDefaultDiskName */
        $fallbackDefaultDiskName = array_key_first($disksConfig);

        $defaultDiskName = $this->default ?? (is_string($fallbackDefaultDiskName) ? $fallbackDefaultDiskName : null);

        if ($defaultDiskName === null || empty(mb_trim($defaultDiskName))) {
            return null;
        }

        $disksMap = $this->getDisksMap();

        if (isset($disksMap[$defaultDiskName])) {
            return $disksMap[$defaultDiskName];
        }

        return array_values($disksMap)[0] ?? null;
    }

    /**
     * @return array<string, Disk>
     */
    public function getDisksMap(): array
    {
        $fsConfig = $this->filesystemsDisks;

        return collect($this->available)
            ->filter(fn (string $diskName): bool => Arr::has($fsConfig, $diskName))
            ->mapWithKeys(
                function (string $diskName) use ($fsConfig): array {
                    $rawDriver      = Arr::get($fsConfig, "{$diskName}.driver");
                    $driver         = is_string($rawDriver) ? $rawDriver : '';
                    $resolvedDriver = $this->resolveDriver($diskName, $driver);

                    return [
                        $diskName => new Disk(
                            driver: $resolvedDriver,
                            name: $diskName,
                            throw: Arr::boolean($fsConfig, "{$diskName}.throw", false),
                            report: Arr::boolean($fsConfig, "{$diskName}.report", false),
                            readOnly: Arr::boolean($fsConfig, "{$diskName}.read-only", false),
                        ),
                    ];
                }
            )
            ->toBase()
            ->all();
    }
}
