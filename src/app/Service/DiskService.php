<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Service;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Kwaadpepper\LaravelStorageManager\Http\Dto\Disk\DiskStatsDto;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Repository\ConfigRepository;

class DiskService
{
    public function __construct(
        private readonly ConfigRepository $configRepository
    ) {
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
        if (! $this->configRepository->isStatsEnabled()) {
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

        $duration = $this->configRepository->getStatsCacheDuration();
        $cacheKey = "sm_disk_stats_{$disk->name}";

        if ($duration > 0) {
            return Cache::remember(
                $cacheKey,
                $duration,
                fn (): DiskStatsDto => $this->computeDiskStats($disk)
            );
        }

        return $this->computeDiskStats($disk);
    }

    public function clearDiskStatsCache(string $diskName): void
    {
        Cache::forget("sm_disk_stats_{$diskName}");
    }

    public function calculateDiskUsedBytes(Disk $disk): int
    {
        $storage    = $disk->getStorageDisk();
        $totalBytes = 0;

        try {
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
        $quota = $this->configRepository->getDiskQuota($disk->name);

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
                $storage   = $disk->getStorageDisk();
                $localPath = $storage->path('');

                if (is_dir($localPath)) {
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
            mode: $disk->driver === 'local' ? 'folder' : 'unlimited',
        );
    }

    /**
     * @return array<string,Disk>
     */
    private function getConfiguredDisks(): array
    {
        return $this->configRepository->getDisksMap();
    }
}
