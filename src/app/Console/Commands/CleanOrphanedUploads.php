<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Kwaadpepper\LaravelStorageManager\Lib\Upload\UploadPathResolver;
use Kwaadpepper\LaravelStorageManager\Lib\Upload\UploadSessionService;

class CleanOrphanedUploads extends Command
{
    protected $signature = 'sm:clean-orphaned-uploads';

    protected $description = 'Clean temporary upload chunk directories older than 24 hours';

    public function handle(UploadPathResolver $pathResolver, UploadSessionService $uploadSessionService): int
    {
        $disk     = $pathResolver->getDisk();
        $basePath = $pathResolver->getBasePath();

        if (! $disk->exists($basePath)) {
            $this->info('No temporary upload directory found.');

            return 0;
        }

        $directories    = $pathResolver->getUploadDirs();
        $nowDatetime    = CarbonImmutable::now();
        $retainDatetime = $nowDatetime->subHours(24);
        $deletedCount   = 0;

        foreach ($directories as $dir) {
            $timestamp   = CarbonImmutable::createFromTimestamp($disk->lastModified($dir));
            $metaContent = $uploadSessionService->readMetadataFromDir($dir);

            if ($metaContent?->createdAt->greaterThan($timestamp)) {
                $timestamp = $metaContent->createdAt;
            }

            if ($timestamp->lessThan($retainDatetime)) {
                $disk->deleteDirectory($dir);
                $deletedCount++;
            }
        }

        $this->info("Cleaned '{$deletedCount}' orphaned upload directories.");

        return 0;
    }
}
