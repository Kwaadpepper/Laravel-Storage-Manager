<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Console\Commands;

use Illuminate\Console\Command;
use Kwaadpepper\LaravelStorageManager\Repository\ConfigRepository;
use Kwaadpepper\LaravelStorageManager\Service\DiskService;
use Kwaadpepper\LaravelStorageManager\Service\TrashService;

class CleanTrash extends Command
{
    protected $signature = 'sm:clean-trash {--days= : Custom retention days} {--disk= : Specific disk to clean}';

    protected $description = 'Clean expired items from trash based on retention policy';

    public function handle(TrashService $trashService, ConfigRepository $configRepository, DiskService $diskService): int
    {
        $daysOption = $this->option('days');
        $days       = is_string($daysOption) && is_numeric($daysOption)
            ? (int) $daysOption
            : $configRepository->getTrashRetentionDays();

        $diskOption = $this->option('disk');
        $targetDisk = null;

        if (is_string($diskOption) && $diskOption !== '') {
            $targetDisk = $diskService->getDisk($diskOption);
        }

        $purgedCount = $trashService->pruneExpired($targetDisk, $days);

        $this->info("Cleaned '{$purgedCount}' expired items from trash.");

        return 0;
    }
}
