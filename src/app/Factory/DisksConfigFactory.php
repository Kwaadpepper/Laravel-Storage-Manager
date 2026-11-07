<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Factory;

use Illuminate\Support\Arr;
use Kwaadpepper\LaravelStorageManager\Config\DisksConfig;

class DisksConfigFactory
{
    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, array<string, scalar|null>>  $filesystemsDisks
     */
    public function build(array $config, array $filesystemsDisks = []): DisksConfig
    {
        $disksAvailableRaw = Arr::array($config, 'disks.available', []);
        /** @var list<string> $availableDisks */
        $availableDisks = array_values(array_filter(
            $disksAvailableRaw,
            static fn (mixed $disk): bool => is_string($disk) && $disk !== ''
        ));

        $rawDefault = Arr::get($config, 'filesystems.default', Arr::get($config, 'disks.default'));
        $default    = is_string($rawDefault) && ! empty(mb_trim($rawDefault)) ? mb_trim($rawDefault) : null;

        return new DisksConfig($availableDisks, $default, $filesystemsDisks);
    }
}
