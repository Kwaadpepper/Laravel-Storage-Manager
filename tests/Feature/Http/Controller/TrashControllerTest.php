<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controller;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Service\TrashService;

describe('TrashController', function (): void {
    beforeEach(function (): void {
        Storage::fake('local');
        Config::set('storage-manager.enabled', true);
        Config::set('storage-manager.auth.enabled', false);
        Config::set('storage-manager.trash.enabled', true);
        Config::set('storage-manager.trash.folder', '.trash');
        Config::set('storage-manager.disks.default', 'local');
        Config::set('storage-manager.disks.available', ['local']);
        app()->forgetInstance(StorageManagerConfig::class);
    });

    it('lists trashed items', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $storage->put('file1.txt', 'content 1');

        $trashService = app()->make(TrashService::class);
        $trashService->moveToTrash(new Disk('local', 'local'), new Path('/file1.txt'));

        // WHEN
        $response = $this->getJson(route('storage-manager.api.trash.list', ['disk' => 'local']));

        // THEN
        $response->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.items.0.name', 'file1.txt');
    });

    it('restores specified trashed items', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $storage->put('restore-me.txt', 'save me');

        $trashService = app()->make(TrashService::class);
        $item         = $trashService->moveToTrash(new Disk('local', 'local'), new Path('/restore-me.txt'));

        // WHEN
        $response = $this->postJson(route('storage-manager.api.trash.restore'), [
            'disk' => 'local',
            'ids'  => [$item->trashId],
        ]);

        // THEN
        $response->assertOk()
            ->assertJsonPath('data.restored.0.id', $item->trashId)
            ->assertJsonPath('data.restored.0.path', '/restore-me.txt');

        $storage->assertExists('restore-me.txt');
    });

    it('purges specified trashed items', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $storage->put('purge-api.txt', 'gone');

        $trashService = app()->make(TrashService::class);
        $item         = $trashService->moveToTrash(new Disk('local', 'local'), new Path('/purge-api.txt'));

        // WHEN
        $response = $this->deleteJson(route('storage-manager.api.trash.purge'), [
            'disk' => 'local',
            'ids'  => [$item->trashId],
        ]);

        // THEN
        $response->assertOk()
            ->assertJsonPath('data.count', 1)
            ->assertJsonPath('data.ids.0', $item->trashId);

        $storage->assertMissing(".trash/meta/{$item->trashId}.json");
    });

    it('empties trash for a disk', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $storage->put('f1.txt', '1');
        $storage->put('f2.txt', '2');

        $trashService = app()->make(TrashService::class);
        $trashService->moveToTrash(new Disk('local', 'local'), new Path('/f1.txt'));
        $trashService->moveToTrash(new Disk('local', 'local'), new Path('/f2.txt'));

        // WHEN
        $response = $this->deleteJson(route('storage-manager.api.trash.empty'), [
            'disk' => 'local',
        ]);

        // THEN
        $response->assertOk()
            ->assertJsonPath('data.count', 2);

        expect($trashService->list(new Disk('local', 'local')))->toBeEmpty();
    });

    it('validates disk and ids on restore and purge', function (): void {
        // GIVEN

        // WHEN
        $response = $this->postJson(route('storage-manager.api.trash.restore'), [
            'disk' => 'nonexistent',
            'ids'  => [],
        ]);

        // THEN
        $response->assertUnprocessable();
    });

    it('returns 422 when attempting to restore or purge a locked item', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $storage->put('file-lock.txt', 'locked content');

        $trashService = app()->make(TrashService::class);
        $item         = $trashService->moveToTrash(new Disk('local', 'local'), new Path('/file-lock.txt'));

        $storage->put(".trash/meta/{$item->trashId}.lock", 'restoring');

        // WHEN
        $response = $this->postJson(route('storage-manager.api.trash.restore'), [
            'disk' => 'local',
            'ids'  => [$item->trashId],
        ]);

        $responsePurge = $this->deleteJson(route('storage-manager.api.trash.purge'), [
            'disk' => 'local',
            'ids'  => [$item->trashId],
        ]);

        // THEN
        $response->assertStatus(422);
        $responsePurge->assertStatus(422);
    });
});
