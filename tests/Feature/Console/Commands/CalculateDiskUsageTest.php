<?php

declare(strict_types=1);

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Console\Commands\CalculateDiskUsage;
use Tests\TestCase;

describe('CalculateDiskUsageCommand', function (): void {
    beforeEach(function (): void {
        assert($this instanceof TestCase);
        Storage::fake('local');
        Config::set('storage-manager.enabled', true);
        Config::set('storage-manager.disks.default', 'local');
        Config::set('storage-manager.disks.available', ['local']);
        Config::set('storage-manager.stats.enabled', true);
        Config::set('storage-manager.stats.usage_cache_duration', 86400);
        App::forgetInstance(StorageManagerConfig::class);
    });

    it('calculates and caches disk usage for all configured disks', function (): void {
        assert($this instanceof TestCase);
        // GIVEN
        Storage::disk('local')->put('file1.txt', str_repeat('A', 1024));
        Storage::disk('local')->put('file2.txt', str_repeat('B', 2048));
        Cache::forget('sm_disk_usage_local');

        // WHEN
        $action = $this->artisan('sm:calculate-disk-usage');

        // THEN
        $action->expectsOutput('Disk usage calculated and cached successfully.')
            ->assertExitCode(0)
            ->run();

        expect(Cache::get('sm_disk_usage_local'))->toBe(3072);
    });

    it('supports specifying a specific disk option', function (): void {
        assert($this instanceof TestCase);
        // GIVEN
        Storage::disk('local')->put('sample.txt', str_repeat('X', 500));
        Cache::forget('sm_disk_usage_local');

        // WHEN
        $action = $this->artisan('sm:calculate-disk-usage', ['--disk' => 'local', '--fresh' => true]);

        // THEN
        $action->expectsOutput('Disk usage calculated and cached successfully.')
            ->assertExitCode(0)
            ->run();

        expect(Cache::get('sm_disk_usage_local'))->toBe(500);
    });
})->covers(CalculateDiskUsage::class);
