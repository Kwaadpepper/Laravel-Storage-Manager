<?php

declare(strict_types=1);

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

    it('rejects stats request with invalid or missing disk', function (): void {
        $responseMissing = $this->getJson(route('storage-manager.api.disks.stats'));
        $responseMissing->assertStatus(422);

        $responseInvalid = $this->getJson(route('storage-manager.api.disks.stats', ['disk' => 'non_existent_disk']));
        $responseInvalid->assertStatus(422);
    });
});
