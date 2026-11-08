<?php

declare(strict_types=1);

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Exception\StorageConnectionException;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Service\DiskService;

describe('DiskService', function (): void {
    beforeEach(function (): void {
        Storage::fake('local');
        Config::set('filesystems.disks.local', [
            'driver' => 'local',
        ]);
        Config::set('storage-manager.disks.available', ['local']);
        Config::set('storage-manager.stats.enabled', true);
        Config::set('storage-manager.stats.cache_duration', 0);
        App::forgetInstance(StorageManagerConfig::class);
        App::forgetInstance(DiskService::class);
    });

    it('selects a disk and returns it', function (): void {
        // GIVEN
        $diskService = resolve(DiskService::class);
        $disk        = new Disk('local', 'local');

        // WHEN
        $result = $diskService->selectDisk($disk);

        // THEN
        expect($result->name)->toBe('local');
        expect($result->driver)->toBe('local');
    });

    it('returns configured disk names list', function (): void {
        // GIVEN
        $diskService = resolve(DiskService::class);

        // WHEN
        $list = $diskService->getDiskNamesList();

        // THEN
        expect($list)->toContain('local');
    });

    it('finds disk by name or throws exception when missing', function (): void {
        // GIVEN
        $diskService = resolve(DiskService::class);

        // WHEN
        $disk = $diskService->getDisk('local');

        // THEN
        expect($disk->name)->toBe('local');
        expect(fn (): Disk => $diskService->getDisk('unknown_disk'))->toThrow(\InvalidArgumentException::class);
    });

    it('delegates calculateDiskUsedBytes to storage gateway', function (): void {
        // GIVEN
        Storage::disk('local')->put('file1.txt', '12345');
        $diskService = resolve(DiskService::class);
        $disk        = new Disk('local', 'local');

        // WHEN
        $bytes = $diskService->calculateDiskUsedBytes($disk);

        // THEN
        expect($bytes)->toBe(5);
    });

    it('propagates exception when calculateDiskUsedBytes encounters a failure', function (): void {
        // GIVEN
        $adapter = \Mockery::mock(FilesystemAdapter::class);
        $adapter->shouldReceive('getDriver')
            ->andThrow(new \RuntimeException('Connection timed out'));
        Storage::set('local', $adapter);
        $diskService = resolve(DiskService::class);
        $disk        = new Disk('local', 'local');

        // WHEN
        $action = fn (): int => $diskService->calculateDiskUsedBytes($disk);

        // THEN
        expect($action)->toThrow(StorageConnectionException::class);
    });

    it('computes disk stats with quota mode when quota is configured', function (): void {
        // GIVEN
        Storage::disk('local')->put('file1.txt', '12345');
        Config::set('storage-manager.stats.quotas.local', 100);
        App::forgetInstance(StorageManagerConfig::class);
        App::forgetInstance(DiskService::class);
        $diskService = resolve(DiskService::class);
        $disk        = new Disk('local', 'local');

        // WHEN
        $stats = $diskService->getDiskStats($disk);

        // THEN
        expect($stats->mode)->toBe('quota');
        expect($stats->total)->toBe(100);
        expect($stats->used)->toBe(5);
        expect($stats->free)->toBe(95);
        expect($stats->percentage)->toBe(5.0);
    });

    it('computes disk stats delegating to gateway when no quota is configured', function (): void {
        // GIVEN
        Storage::disk('local')->put('file1.txt', '12345');
        $diskService = resolve(DiskService::class);
        $disk        = new Disk('local', 'local');

        // WHEN
        $stats = $diskService->getDiskStats($disk);

        // THEN
        expect($stats->mode)->toBeIn(['partition', 'folder']);
        expect($stats->supported)->toBeTrue();
    });

    it('clears disk stats cache without error', function (): void {
        // GIVEN
        $diskService = resolve(DiskService::class);
        $disk        = new Disk('local', 'local');

        // WHEN
        $diskService->clearDiskStatsCache($disk);

        // THEN
        expect(true)->toBeTrue();
    });

    it('returns cached used bytes when available in calculateDiskUsedBytes', function (): void {
        // GIVEN
        Cache::put('sm_disk_usage_s3', 42000, 3600);
        $diskService = resolve(DiskService::class);
        $disk        = new Disk('s3', 's3');

        // WHEN
        $bytes = $diskService->calculateDiskUsedBytes($disk);

        // THEN
        expect($bytes)->toBe(42000);
    });

    it('safely falls back to unlimited mode on remote disk with quota when usage is not cached and realtime usage is disabled', function (): void {
        // GIVEN
        Cache::forget('sm_disk_usage_s3');
        Config::set('storage-manager.stats.quotas.s3', 500000);
        Config::set('storage-manager.stats.realtime_remote_usage', false);
        App::forgetInstance(StorageManagerConfig::class);
        App::forgetInstance(DiskService::class);
        $diskService = resolve(DiskService::class);
        $disk        = new Disk('s3', 's3');

        // WHEN
        $stats = $diskService->getDiskStats($disk);

        // THEN
        expect($stats->mode)->toBe('unlimited');
        expect($stats->used)->toBeNull();
        expect($stats->total)->toBe(500000);
    });

    it('clears disk usage cache via clearDiskUsageCache', function (): void {
        // GIVEN
        Cache::put('sm_disk_usage_local', 1234, 3600);
        $diskService = resolve(DiskService::class);
        $disk        = new Disk('local', 'local');

        // WHEN
        $diskService->clearDiskUsageCache($disk);

        // THEN
        expect(Cache::has('sm_disk_usage_local'))->toBeFalse();
    });

    it('computes stats using quota configured directly on Disk', function (): void {
        // GIVEN
        $diskService = resolve(DiskService::class);
        $disk        = new Disk('local', 'local', quota: 2000000);

        // WHEN
        $stats = $diskService->getDiskStats($disk);

        // THEN
        expect($stats->mode)->toBe('quota');
        expect($stats->total)->toBe(2000000);
    });
})->covers(DiskService::class);
