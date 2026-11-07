<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Service;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Arr;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Event\DiskSelected;
use Kwaadpepper\LaravelStorageManager\Http\Dto\Disk\DiskStatsDto;
use Kwaadpepper\LaravelStorageManager\Lib\Event\EventDispatcher;
use Kwaadpepper\LaravelStorageManager\Lib\StorageGateway;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use League\Flysystem\FileAttributes;

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
        return array_values(array_map(
            fn (Disk $disk): string => $disk->name,
            $this->getConfiguredDisks()
        ));
    }

    public function getDisk(string $name): Disk
    {
        return Arr::first(
            $this->getConfiguredDisks(),
            fn (Disk $disk): bool => $disk->name === $name
        ) ?? throw new \InvalidArgumentException(
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

    public function clearDiskStatsCache(string $diskName): void
    {
        $this->cache->forget("sm_disk_stats_{$diskName}");
    }

    public function calculateDiskUsedBytes(Disk $disk): int
    {
        /** @var Filesystem $storage */
        $storage    = $this->storageGateway->disk($disk);
        $totalBytes = 0;

        try {
            if ($storage instanceof FilesystemAdapter) {
                foreach ($storage->getDriver()->listContents('', true) as $item) {
                    if ($item->isFile() && $item instanceof FileAttributes) {
                        $totalBytes += $item->fileSize() ?? 0;
                    }
                }

                return $totalBytes;
            }

            $files = $storage->allFiles();
            foreach ($files as $file) {
                try {
                    $totalBytes += $storage->size($file);
                } catch (\Throwable $fileEx) {
                    unset($fileEx);
                }
            }
        } catch (\Throwable $listEx) {
            unset($listEx);
        }

        return $totalBytes;
    }

    private function computeDiskStats(Disk $disk): DiskStatsDto
    {
        $quota = $this->config->stats->getDiskQuota($disk->name);

        if ($quota !== null) {
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

        if ($disk->driver === 'local') {
            try {
                $localPath = $this->storageGateway->localPath($disk);

                if ($localPath !== null && is_dir($localPath)) {
                    $totalSpace = disk_total_space($localPath);
                    $freeSpace  = disk_free_space($localPath);

                    if ($totalSpace !== false && $freeSpace !== false && $totalSpace > 0) {
                        $totalInt   = (int) $totalSpace;
                        $freeInt    = (int) $freeSpace;
                        $usedInt    = max(0, $totalInt - $freeInt);
                        $percentage = round(($usedInt / $totalInt) * 100, 1);

                        return new DiskStatsDto(
                            disk: $disk->name,
                            driver: $disk->driver,
                            supported: true,
                            total: $totalInt,
                            free: $freeInt,
                            used: $usedInt,
                            percentage: $percentage,
                            mode: 'partition',
                        );
                    }
                }
            } catch (\Throwable $diskEx) {
                unset($diskEx);
            }

            $used = $this->calculateDiskUsedBytes($disk);

            return new DiskStatsDto(
                disk: $disk->name,
                driver: $disk->driver,
                supported: true,
                total: null,
                free: null,
                used: $used,
                percentage: null,
                mode: 'folder',
            );
        }

        return new DiskStatsDto(
            disk: $disk->name,
            driver: $disk->driver,
            supported: true,
            total: null,
            free: null,
            used: null,
            percentage: null,
            mode: 'unlimited',
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
