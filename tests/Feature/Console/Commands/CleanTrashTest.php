<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Service\TrashService;
use Tests\TestCase;

describe('CleanTrashCommand', function (): void {
    beforeEach(function (): void {
        assert($this instanceof TestCase);
        CarbonImmutable::setTestNow('2026-01-01 12:00:00');
        Storage::fake('local');
        Config::set('storage-manager.enabled', true);
        Config::set('storage-manager.trash.enabled', true);
        Config::set('storage-manager.trash.folder', '.trash');
        Config::set('storage-manager.trash.retention_days', 30);
        Config::set('storage-manager.disks.default', 'local');
        Config::set('storage-manager.disks.available', ['local']);
        App::forgetInstance(StorageManagerConfig::class);
    });

    afterEach(function (): void {
        CarbonImmutable::setTestNow();
    });

    it('cleans expired items from trash', function (): void {
        assert($this instanceof TestCase);
        // GIVEN
        $storage = Storage::disk('local');
        $storage->put('expired.txt', 'expired data');
        $storage->put('fresh.txt', 'fresh data');

        $trashService = resolve(TrashService::class);
        $disk         = new Disk('local', 'local');

        $expiredItem = $trashService->moveToTrash($disk, new Path('/expired.txt'));
        $freshItem   = $trashService->moveToTrash($disk, new Path('/fresh.txt'));

        $metaPath = ".trash/meta/{$expiredItem->trashId}.json";
        $metaData = json_decode((string) $storage->get($metaPath), true);
        assert(is_array($metaData));
        $metaData['deletedAt'] = CarbonImmutable::now()->subDays(35)->toIso8601String();
        $storage->put($metaPath, (string) json_encode($metaData));

        // WHEN
        $action = $this->artisan('sm:clean-trash');

        // THEN
        $action->expectsOutput("Cleaned '1' expired items from trash.")
            ->assertExitCode(0)
            ->run();

        expect($trashService->getItem($disk, $expiredItem->trashId))->toBeNull()
            ->and($trashService->getItem($disk, $freshItem->trashId))->not->toBeNull();
    });

    it('supports custom days and disk options', function (): void {
        assert($this instanceof TestCase);
        // GIVEN
        $storage = Storage::disk('local');
        $storage->put('custom.txt', 'custom data');

        $trashService = resolve(TrashService::class);
        $disk         = new Disk('local', 'local');

        $item = $trashService->moveToTrash($disk, new Path('/custom.txt'));

        $metaPath = ".trash/meta/{$item->trashId}.json";
        $metaData = json_decode((string) $storage->get($metaPath), true);
        assert(is_array($metaData));
        $metaData['deletedAt'] = CarbonImmutable::now()->subDays(10)->toIso8601String();
        $storage->put($metaPath, (string) json_encode($metaData));

        // WHEN
        $action = $this->artisan('sm:clean-trash --days=7 --disk=local');

        // THEN
        $action->expectsOutput("Cleaned '1' expired items from trash.")
            ->assertExitCode(0)
            ->run();

        expect($trashService->getItem($disk, $item->trashId))->toBeNull();
    });
});
