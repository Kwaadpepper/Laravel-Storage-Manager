<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Service;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Event\DiskSelected;
use Kwaadpepper\LaravelStorageManager\Http\Dto\Disk\DiskStatsDto;
use Kwaadpepper\LaravelStorageManager\Lib\Event\EventDispatcher;
use Kwaadpepper\LaravelStorageManager\Lib\StorageGateway;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;

class DiskService
{
    public function __construct(
        private readonly StorageManagerConfig $config,
        private readonly EventDispatcher $eventDispatcher,
        private readonly CacheRepository $cache,
        private readonly StorageGateway $storageGateway,
    ) {
    }

    public function selectDisk(Disk $disk): Disk
    {
        $this->eventDispatcher->dispatch(DiskSelected::class, [
            'disk' => $disk->name,
        ]);

        return $disk;
    }

    /**
     * @return list<string>
     */
    public function getDiskNamesList(): array
    {
        return array_keys($this->getConfiguredDisks());
    }

    public function getDisk(string $name): Disk
    {
        return $this->config->disks->getDisk($name) ?? throw new \InvalidArgumentException(
            "Disk with name '{$name}' not found."
        );
    }

    public function getDiskStats(Disk $disk): DiskStatsDto
    {
        if (! $this->config->stats->enabled) {
            return new DiskStatsDto(
                disk: $disk->name,
                driver: $disk->driver,
                supported: false,
                total: null,
                free: null,
                used: null,
                percentage: null,
                mode: 'disabled',
            );
        }

        $duration = $this->config->stats->cacheDuration;
        $cacheKey = "sm_disk_stats_{$disk->name}";

        if ($duration > 0) {
            $cached = $this->cache->get($cacheKey);
            if (is_array($cached)) {
                try {
                    return DiskStatsDto::fromArray($cached);
                } catch (\Throwable $e) {
                    unset($e);
                }
            }
        }

        $stats = $this->computeDiskStats($disk);

        if ($duration > 0) {
            $this->cache->put($cacheKey, $stats->toArray(), $duration);
        }

        return $stats;
    }

    public function clearDiskStatsCache(Disk $disk): void
    {
        $this->cache->forget("sm_disk_stats_{$disk->name}");
    }

    public function clearDiskUsageCache(Disk $disk): void
    {
        $this->cache->forget("sm_disk_usage_{$disk->name}");
    }

    public function calculateDiskUsedBytes(Disk $disk, bool $fresh = false): int
    {
        $cacheKey = "sm_disk_usage_{$disk->name}";

        if (! $fresh) {
            $cached = $this->cache->get($cacheKey);
            if (is_int($cached) && $cached >= 0) {
                return $cached;
            }
        }

        if ($disk->isRemote() && ! $this->config->stats->realtimeRemoteUsage && ! $fresh) {
            return 0;
        }

        $used = $this->storageGateway->calculateDiskUsedBytes($disk);

        $ttl = $this->config->stats->usageCacheDuration;
        if ($ttl > 0) {
            $this->cache->put($cacheKey, $used, $ttl);
        }

        return $used;
    }

    private function computeDiskStats(Disk $disk): DiskStatsDto
    {
        $quota = $disk->quota ?? $this->config->stats->getDiskQuota($disk->name);

        if ($quota !== null) {
            $cacheKey = "sm_disk_usage_{$disk->name}";
            $hasCache = $this->cache->has($cacheKey);
            $isRemote = $disk->isRemote();

            if ($isRemote && ! $this->config->stats->realtimeRemoteUsage && ! $hasCache) {
                return new DiskStatsDto(
                    disk: $disk->name,
                    driver: $disk->driver,
                    supported: true,
                    total: $quota,
                    free: null,
                    used: null,
                    percentage: null,
                    mode: 'unlimited',
                );
            }

            $used       = $this->calculateDiskUsedBytes($disk);
            $free       = max(0, $quota - $used);
            $percentage = $quota > 0 ? round(($used / $quota) * 100, 1) : 0.0;

            return new DiskStatsDto(
                disk: $disk->name,
                driver: $disk->driver,
                supported: true,
                total: $quota,
                free: $free,
                used: $used,
                percentage: $percentage,
                mode: 'quota',
            );
        }

        $driverStats = $this->storageGateway->getDiskStats($disk);

        return new DiskStatsDto(
            disk: $disk->name,
            driver: $disk->driver,
            supported: true,
            total: $driverStats->total,
            free: $driverStats->free,
            used: $driverStats->used,
            percentage: $driverStats->percentage,
            mode: $driverStats->mode,
        );
    }

    /**
     * @return array<string,Disk>
     */
    private function getConfiguredDisks(): array
    {
        return $this->config->disks->getDisksMap();
    }
}
