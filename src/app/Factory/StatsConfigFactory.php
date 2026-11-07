<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Factory;

use Illuminate\Support\Arr;
use Kwaadpepper\LaravelStorageManager\Config\StatsConfig;

class StatsConfigFactory
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function build(array $config): StatsConfig
    {
        $enabled = Arr::boolean($config, 'stats.enabled', true);

        $statsCacheDuration = Arr::get($config, 'stats.cache_duration', 60);
        $statsCacheDuration = is_int($statsCacheDuration) && $statsCacheDuration >= 0 ? $statsCacheDuration : 60;

        $rawQuotas = Arr::array($config, 'stats.quotas', []);
        $quotas    = [];
        foreach ($rawQuotas as $diskName => $quota) {
            if (is_string($diskName) && is_int($quota) && $quota > 0) {
                $quotas[$diskName] = $quota;
            }
        }

        return new StatsConfig($enabled, $statsCacheDuration, $quotas);
    }
}
