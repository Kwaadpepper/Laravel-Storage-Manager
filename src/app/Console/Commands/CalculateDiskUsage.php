<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Number;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Service\DiskService;

class CalculateDiskUsage extends Command
{
    protected $signature = 'sm:calculate-disk-usage {--disk= : Specific disk to calculate usage for} {--fresh : Bypass cached usage}';

    protected $description = 'Calculate and cache disk usage for storage disks';

    public function handle(DiskService $diskService, StorageManagerConfig $config): int
    {
        $diskOption = $this->option('disk');
        $fresh      = (bool) $this->option('fresh');

        if (is_string($diskOption) && $diskOption !== '') {
            $disks = [$diskService->getDisk($diskOption)];
        } else {
            $disks = array_values($config->disks->getDisksMap());
        }

        if (empty($disks)) {
            $this->warn('No configured disks found.');

            return 0;
        }

        $rows = [];

        foreach ($disks as $disk) {
            $startTime = microtime(true);
            $usedBytes = $diskService->calculateDiskUsedBytes($disk, fresh: $fresh);
            $diskService->clearDiskStatsCache($disk);
            $elapsed = round(microtime(true) - $startTime, 2);

            $rows[] = [
                'disk'     => $disk->name,
                'driver'   => $disk->driver,
                'usage'    => Number::fileSize($usedBytes),
                'bytes'    => number_format($usedBytes),
                'duration' => "{$elapsed}s",
            ];
        }

        $this->table(['Disk', 'Driver', 'Used Size', 'Bytes', 'Time'], $rows);
        $this->info('Disk usage calculated and cached successfully.');

        return 0;
    }
}
