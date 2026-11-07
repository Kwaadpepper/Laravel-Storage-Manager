<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Service\DiskService;

describe('DiskStats', function (): void {
    it('can retrieve disk stats for an existing disk', function (): void {
        // GIVEN
        Storage::fake('local');
        Storage::disk('local')->put('sample.txt', 'Sample file content for stats test');

        // WHEN
        $response = $this->getJson(route('storage-manager.api.disks.stats', ['disk' => 'local']));

        // THEN
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'disk',
                    'driver',
                    'supported',
                    'total',
                    'free',
                    'used',
                    'percentage',
                    'mode',
                ],
            ]);

        expect($response->json('data.disk'))->toBe('local');
        expect($response->json('data.supported'))->toBeTrue();
        expect($response->json('data.mode'))->toBeIn(['partition', 'folder']);
    });

    it('respects configured quota on a disk', function (): void {
        // GIVEN
        Storage::fake('local');
        Storage::disk('local')->put('hello.txt', str_repeat('X', 5000));

        Config::set('storage-manager.stats.quotas.local', 100000);
        Config::set('storage-manager.stats.cache_duration', 0);
        app()->forgetInstance(StorageManagerConfig::class);
        app()->forgetInstance(DiskService::class);

        $diskService = resolve(DiskService::class);
        $diskService->clearDiskStatsCache(new Disk('local', 'local'));

        // WHEN
        $response = $this->getJson(route('storage-manager.api.disks.stats', ['disk' => 'local']));

        // THEN
        $response->assertStatus(200);
        expect($response->json('data.disk'))->toBe('local');
        expect($response->json('data.mode'))->toBe('quota');
        expect($response->json('data.total'))->toBe(100000);
        expect($response->json('data.used'))->toBe(5000);
        expect($response->json('data.free'))->toBe(95000);
        expect($response->json('data.percentage'))->toEqual(5.0);
    });

    it('returns disabled mode when stats are disabled in config', function (): void {
        // GIVEN
        Storage::fake('local');
        Config::set('storage-manager.stats.enabled', false);
        app()->forgetInstance(StorageManagerConfig::class);
        app()->forgetInstance(DiskService::class);

        // WHEN
        $response = $this->getJson(route('storage-manager.api.disks.stats', ['disk' => 'local']));

        // THEN
        $response->assertStatus(200);
        expect($response->json('data.supported'))->toBeFalse();
        expect($response->json('data.mode'))->toBe('disabled');
        expect($response->json('data.total'))->toBeNull();
        expect($response->json('data.used'))->toBeNull();

        Config::set('storage-manager.stats.enabled', true);
        app()->forgetInstance(StorageManagerConfig::class);
        app()->forgetInstance(DiskService::class);
    });

    it('supports fresh parameter to bypass cache', function (): void {
        // GIVEN
        Storage::fake('local');

        // WHEN
        $response = $this->getJson(route('storage-manager.api.disks.stats', [
            'disk'  => 'local',
            'fresh' => 1,
        ]));

        // THEN
        $response->assertStatus(200);
        expect($response->json('data.disk'))->toBe('local');
    });

    it('handles s3 cloud disk in unlimited mode without quota', function (): void {
        // GIVEN
        Storage::fake('s3');
        Config::set('filesystems.disks.s3', [
            'driver' => 's3',
            'key'    => 'test',
            'secret' => 'test',
            'region' => 'us-east-1',
            'bucket' => 'test-bucket',
        ]);
        Config::set('storage-manager.disks.available', ['local', 's3']);
        app()->forgetInstance(StorageManagerConfig::class);
        app()->forgetInstance(DiskService::class);

        // WHEN
        $response = $this->getJson(route('storage-manager.api.disks.stats', ['disk' => 's3']));

        // THEN
        $response->assertStatus(200);
        expect($response->json('data.disk'))->toBe('s3');
        expect($response->json('data.driver'))->toBe('s3');
        expect($response->json('data.mode'))->toBe('unlimited');
        expect($response->json('data.total'))->toBeNull();
        expect($response->json('data.free'))->toBeNull();
        expect($response->json('data.used'))->toBeNull();
        expect($response->json('data.percentage'))->toBeNull();
    });

    it('gracefully recovers from legacy corrupted cache entries without throwing TypeError', function (): void {
        // GIVEN
        Storage::fake('local');
        Config::set('storage-manager.stats.cache_duration', 3600);
        app()->forgetInstance(StorageManagerConfig::class);
        app()->forgetInstance(DiskService::class);

        Cache::put('sm_disk_stats_local', unserialize('O:20:"NonExistentTestClass":0:{}'), 3600);

        // WHEN
        $response = $this->getJson(route('storage-manager.api.disks.stats', ['disk' => 'local']));

        // THEN
        $response->assertStatus(200);
        expect($response->json('data.disk'))->toBe('local');
        expect($response->json('data.supported'))->toBeTrue();
    });

    it('serves stats from array cache on subsequent requests', function (): void {
        // GIVEN
        Storage::fake('local');
        Config::set('storage-manager.stats.cache_duration', 3600);
        app()->forgetInstance(StorageManagerConfig::class);
        app()->forgetInstance(DiskService::class);

        $first = $this->getJson(route('storage-manager.api.disks.stats', ['disk' => 'local']));
        $first->assertStatus(200);

        $cached = Cache::get('sm_disk_stats_local');
        expect($cached)->toBeArray();
        expect($cached['disk'])->toBe('local');

        // WHEN
        $second = $this->getJson(route('storage-manager.api.disks.stats', ['disk' => 'local']));

        // THEN
        $second->assertStatus(200);
        expect($second->json('data.disk'))->toBe('local');
    });

    it('rejects stats request with invalid or missing disk', function (): void {
        // GIVEN

        // WHEN
        $responseMissing = $this->getJson(route('storage-manager.api.disks.stats'));
        $responseInvalid = $this->getJson(route('storage-manager.api.disks.stats', ['disk' => 'non_existent_disk']));

        // THEN
        $responseMissing->assertStatus(422);
        $responseInvalid->assertStatus(422);
    });
});
