<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Repository\ConfigRepository;
use Kwaadpepper\LaravelStorageManager\Service\DiskService;

describe('DiskStats', function () {

    it('can retrieve disk stats for an existing disk', function (): void {
        Storage::fake('local');
        Storage::disk('local')->put('sample.txt', 'Sample file content for stats test');

        $response = $this->getJson(route('storage-manager.api.disks.stats', ['disk' => 'local']));

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
        Storage::fake('local');
        Storage::disk('local')->put('hello.txt', str_repeat('X', 5000));

        Config::set('storage-manager.stats.quotas.local', 100000);
        Config::set('storage-manager.stats.cache_duration', 0);
        app()->forgetInstance(ConfigRepository::class);
        app()->forgetInstance(DiskService::class);

        /** @var DiskService $diskService */
        $diskService = resolve(DiskService::class);
        $diskService->clearDiskStatsCache('local');

        $response = $this->getJson(route('storage-manager.api.disks.stats', ['disk' => 'local']));

        $response->assertStatus(200);
        expect($response->json('data.disk'))->toBe('local');
        expect($response->json('data.mode'))->toBe('quota');
        expect($response->json('data.total'))->toBe(100000);
        expect($response->json('data.used'))->toBe(5000);
        expect($response->json('data.free'))->toBe(95000);
        expect($response->json('data.percentage'))->toEqual(5.0);
    });

    it('returns disabled mode when stats are disabled in config', function (): void {
        Storage::fake('local');
        Config::set('storage-manager.stats.enabled', false);
        app()->forgetInstance(ConfigRepository::class);
        app()->forgetInstance(DiskService::class);

        $response = $this->getJson(route('storage-manager.api.disks.stats', ['disk' => 'local']));

        $response->assertStatus(200);
        expect($response->json('data.supported'))->toBeFalse();
        expect($response->json('data.mode'))->toBe('disabled');
        expect($response->json('data.total'))->toBeNull();
        expect($response->json('data.used'))->toBeNull();

        Config::set('storage-manager.stats.enabled', true);
        app()->forgetInstance(ConfigRepository::class);
        app()->forgetInstance(DiskService::class);
    });

    it('supports fresh parameter to bypass cache', function (): void {
        Storage::fake('local');
        $response = $this->getJson(route('storage-manager.api.disks.stats', [
            'disk'  => 'local',
            'fresh' => 1,
        ]));

        $response->assertStatus(200);
        expect($response->json('data.disk'))->toBe('local');
    });

    it('handles s3 cloud disk in unlimited mode without quota', function (): void {
        Storage::fake('s3');
        Config::set('filesystems.disks.s3', [
            'driver' => 's3',
            'key'    => 'test',
            'secret' => 'test',
            'region' => 'us-east-1',
            'bucket' => 'test-bucket',
        ]);
        Config::set('storage-manager.disks.available', ['local', 's3']);
        app()->forgetInstance(ConfigRepository::class);
        app()->forgetInstance(DiskService::class);

        $response = $this->getJson(route('storage-manager.api.disks.stats', ['disk' => 's3']));

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
        Storage::fake('local');
        Config::set('storage-manager.stats.cache_duration', 3600);
        app()->forgetInstance(ConfigRepository::class);
        app()->forgetInstance(DiskService::class);

        // Simulate an incomplete class stored in cache
        Cache::put('sm_disk_stats_local', unserialize('O:20:"NonExistentTestClass":0:{}'), 3600);

        $response = $this->getJson(route('storage-manager.api.disks.stats', ['disk' => 'local']));

        $response->assertStatus(200);
        expect($response->json('data.disk'))->toBe('local');
        expect($response->json('data.supported'))->toBeTrue();
    });

    it('serves stats from array cache on subsequent requests', function (): void {
        Storage::fake('local');
        Config::set('storage-manager.stats.cache_duration', 3600);
        app()->forgetInstance(ConfigRepository::class);
        app()->forgetInstance(DiskService::class);

        // Prime the cache
        $first = $this->getJson(route('storage-manager.api.disks.stats', ['disk' => 'local']));
        $first->assertStatus(200);

        // Verify cache contains array representation
        $cached = Cache::get('sm_disk_stats_local');
        expect($cached)->toBeArray();
        expect($cached['disk'])->toBe('local');

        // Second request hits the array cache
        $second = $this->getJson(route('storage-manager.api.disks.stats', ['disk' => 'local']));
        $second->assertStatus(200);
        expect($second->json('data.disk'))->toBe('local');
    });

    it('rejects stats request with invalid or missing disk', function (): void {
        $responseMissing = $this->getJson(route('storage-manager.api.disks.stats'));
        $responseMissing->assertStatus(422);

        $responseInvalid = $this->getJson(route('storage-manager.api.disks.stats', ['disk' => 'non_existent_disk']));
        $responseInvalid->assertStatus(422);
    });
});
