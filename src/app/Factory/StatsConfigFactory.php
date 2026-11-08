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
        $enabled                  = Arr::boolean($config, 'stats.enabled', true);
        $statsCacheDuration       = Arr::integer($config, 'stats.cache_duration', 60);
        $realtimeRemoteUsage      = Arr::boolean($config, 'stats.realtime_remote_usage', false);
        $usageCacheDuration       = Arr::integer($config, 'stats.usage_cache_duration', 86400);
        $scheduleUsageCalculation = Arr::boolean($config, 'stats.schedule_usage_calculation', true);

        $rawQuotas = Arr::array($config, 'stats.quotas', []);
        $quotas    = [];
        foreach ($rawQuotas as $diskName => $quota) {
            if (is_string($diskName) && is_numeric($quota) && (int) $quota > 0) {
                $quotas[$diskName] = (int) $quota;
            }
        }

        return new StatsConfig(
            $enabled,
            $statsCacheDuration >= 0 ? $statsCacheDuration : 60,
            $quotas,
            $realtimeRemoteUsage,
            $usageCacheDuration >= 0 ? $usageCacheDuration : 86400,
            $scheduleUsageCalculation
        );
    }
}
