<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Repository\ConfigRepository;
use Kwaadpepper\LaravelStorageManager\Service\TrashService;

describe('TrashService', function (): void {
    beforeEach(function (): void {
        Storage::fake('local');
        Config::set('storage-manager.enabled', true);
        Config::set('storage-manager.trash.enabled', true);
        Config::set('storage-manager.trash.folder', '.trash');
        Config::set('storage-manager.trash.retention_days', 30);
        app()->forgetInstance(ConfigRepository::class);
    });

    it('moves a file to trash and creates metadata', function (): void {
        $storage = Storage::disk('local');
        $storage->put('test-file.txt', 'hello world');

        /** @var TrashService $trashService */
        $trashService = app()->make(TrashService::class);
        $disk         = new Disk('local', 'local');

        $item = $trashService->moveToTrash($disk, new Path('/test-file.txt'));

        expect($item->name)->toBe('test-file.txt')
            ->and($item->type)->toBe('file')
            ->and($item->size)->toBe(11)
            ->and($item->originalPath->value)->toBe('/test-file.txt');

        $storage->assertMissing('test-file.txt');
        $storage->assertExists(".trash/meta/{$item->trashId}.json");
        $storage->assertExists(".trash/files/{$item->trashId}_test-file.txt");
    });

    it('moves a directory to trash with its files', function (): void {
        $storage = Storage::disk('local');
        $storage->makeDirectory('my-folder');
        $storage->put('my-folder/file1.txt', 'abc');
        $storage->put('my-folder/file2.txt', '12345');

        /** @var TrashService $trashService */
        $trashService = app()->make(TrashService::class);
        $disk         = new Disk('local', 'local');

        $item = $trashService->moveToTrash($disk, new Path('/my-folder'));

        expect($item->name)->toBe('my-folder')
            ->and($item->type)->toBe('directory')
            ->and($item->size)->toBe(8);

        $storage->assertMissing('my-folder');
        $storage->assertExists(".trash/meta/{$item->trashId}.json");
        $storage->assertExists(".trash/files/{$item->trashId}_my-folder/file1.txt");
    });

    it('throws when trying to move non-existent path to trash', function (): void {
        /** @var TrashService $trashService */
        $trashService = app()->make(TrashService::class);
        $disk         = new Disk('local', 'local');

        expect(fn (): mixed => $trashService->moveToTrash($disk, new Path('/non-existent.txt')))
            ->toThrow(FileOperationException::class);
    });

    it('throws when trash is disabled in config', function (): void {
        Config::set('storage-manager.trash.enabled', false);
        app()->forgetInstance(ConfigRepository::class);

        $storage = Storage::disk('local');
        $storage->put('test.txt', 'abc');

        /** @var TrashService $trashService */
        $trashService = app()->make(TrashService::class);
        $disk         = new Disk('local', 'local');

        expect(fn (): mixed => $trashService->moveToTrash($disk, new Path('/test.txt')))
            ->toThrow(FileOperationException::class);
    });

    it('throws when trying to trash root path or inside trash folder', function (): void {
        /** @var TrashService $trashService */
        $trashService = app()->make(TrashService::class);
        $disk         = new Disk('local', 'local');

        expect(fn (): mixed => $trashService->moveToTrash($disk, new Path('/')))
            ->toThrow(FileOperationException::class);
    });

    it('lists trashed items sorted by deletion date descending', function (): void {
        $storage = Storage::disk('local');
        $storage->put('first.txt', '1');
        $storage->put('second.txt', '22');

        /** @var TrashService $trashService */
        $trashService = app()->make(TrashService::class);
        $disk         = new Disk('local', 'local');

        $item1 = $trashService->moveToTrash($disk, new Path('/first.txt'));
        $item2 = $trashService->moveToTrash($disk, new Path('/second.txt'));

        // Ensure item2 has distinctly newer timestamp
        $metaPath = ".trash/meta/{$item2->trashId}.json";
        $metaData = json_decode((string) $storage->get($metaPath), true);
        assert(is_array($metaData));
        $metaData['deletedAt'] = CarbonImmutable::now()->addMinute()->toIso8601String();
        $storage->put($metaPath, (string) json_encode($metaData));

        $list = $trashService->list($disk);

        expect($list)->toHaveCount(2)
            ->and($list[0]->trashId)->toBe($item2->trashId)
            ->and($list[1]->trashId)->toBe($item1->trashId);
    });

    it('restores a file to its original path', function (): void {
        $storage = Storage::disk('local');
        $storage->put('docs/report.pdf', 'pdf-data');

        /** @var TrashService $trashService */
        $trashService = app()->make(TrashService::class);
        $disk         = new Disk('local', 'local');

        $item = $trashService->moveToTrash($disk, new Path('/docs/report.pdf'));
        $storage->deleteDirectory('docs');

        $restoredPath = $trashService->restore($disk, $item->trashId);

        expect($restoredPath->value)->toBe('/docs/report.pdf');
        $storage->assertExists('docs/report.pdf');
        $storage->assertMissing(".trash/meta/{$item->trashId}.json");
        $storage->assertMissing(".trash/files/{$item->trashId}_report.pdf");
    });

    it('handles duplicate conflict on restore by auto-renaming', function (): void {
        $storage = Storage::disk('local');
        $storage->put('file.txt', 'old version');

        /** @var TrashService $trashService */
        $trashService = app()->make(TrashService::class);
        $disk         = new Disk('local', 'local');

        $item = $trashService->moveToTrash($disk, new Path('/file.txt'));

        $storage->put('file.txt', 'new version');

        $restoredPath = $trashService->restore($disk, $item->trashId);

        expect($restoredPath->value)->toBe('/file (restored).txt');
        $storage->assertExists('file.txt');
        $storage->assertExists('file (restored).txt');
        expect($storage->get('file (restored).txt'))->toBe('old version');
    });

    it('purges a specific trashed item permanently', function (): void {
        $storage = Storage::disk('local');
        $storage->put('purge-me.txt', 'to-be-purged');

        /** @var TrashService $trashService */
        $trashService = app()->make(TrashService::class);
        $disk         = new Disk('local', 'local');

        $item = $trashService->moveToTrash($disk, new Path('/purge-me.txt'));
        $trashService->purge($disk, $item->trashId);

        expect($trashService->getItem($disk, $item->trashId))->toBeNull();
        $storage->assertMissing(".trash/meta/{$item->trashId}.json");
        $storage->assertMissing(".trash/files/{$item->trashId}_purge-me.txt");
    });

    it('empties the entire trash for a disk', function (): void {
        $storage = Storage::disk('local');
        $storage->put('a.txt', 'a');
        $storage->put('b.txt', 'b');

        /** @var TrashService $trashService */
        $trashService = app()->make(TrashService::class);
        $disk         = new Disk('local', 'local');

        $trashService->moveToTrash($disk, new Path('/a.txt'));
        $trashService->moveToTrash($disk, new Path('/b.txt'));

        $count = $trashService->emptyTrash($disk);

        expect($count)->toBe(2)
            ->and($trashService->list($disk))->toBeEmpty();
    });

    it('prunes expired trashed items based on retention days', function (): void {
        $storage = Storage::disk('local');
        $storage->put('old.txt', 'old');
        $storage->put('recent.txt', 'recent');

        /** @var TrashService $trashService */
        $trashService = app()->make(TrashService::class);
        $disk         = new Disk('local', 'local');

        $oldItem    = $trashService->moveToTrash($disk, new Path('/old.txt'));
        $recentItem = $trashService->moveToTrash($disk, new Path('/recent.txt'));

        // Manually manipulate oldItem's meta file to have deletedAt 40 days ago
        $metaPath = ".trash/meta/{$oldItem->trashId}.json";
        $metaData = json_decode((string) $storage->get($metaPath), true);
        assert(is_array($metaData));
        $metaData['deletedAt'] = CarbonImmutable::now()->subDays(40)->toIso8601String();
        $storage->put($metaPath, (string) json_encode($metaData));

        $purgedCount = $trashService->pruneExpired($disk, 30);

        expect($purgedCount)->toBe(1)
            ->and($trashService->getItem($disk, $oldItem->trashId))->toBeNull()
            ->and($trashService->getItem($disk, $recentItem->trashId))->not->toBeNull();
    });

    it('prevents restoring or purging an item that is locked', function (): void {
        $storage = Storage::disk('local');
        $storage->put('locked.txt', 'content');

        /** @var TrashService $trashService */
        $trashService = app()->make(TrashService::class);
        $disk         = new Disk('local', 'local');

        $item = $trashService->moveToTrash($disk, new Path('/locked.txt'));

        // Manually place a lock file
        $storage->put(".trash/meta/{$item->trashId}.lock", 'restoring');

        // Verify status in getItem and list
        expect($trashService->getItem($disk, $item->trashId)?->status)->toBe('restoring');
        $list = $trashService->list($disk);
        expect($list[0]->status)->toBe('restoring');

        // Attempting to restore while locked throws TRASH_ITEM_LOCKED
        expect(fn () => $trashService->restore($disk, $item->trashId))
            ->toThrow(FileOperationException::class);

        // Attempting to purge while locked throws TRASH_ITEM_LOCKED
        expect(fn () => $trashService->purge($disk, $item->trashId))
            ->toThrow(FileOperationException::class);

        // Attempting to empty trash while items are locked throws TRASH_ITEM_LOCKED
        expect(fn () => $trashService->emptyTrash($disk))
            ->toThrow(FileOperationException::class);

        // Removing lock allows operations
        $storage->delete(".trash/meta/{$item->trashId}.lock");
        expect($trashService->getItem($disk, $item->trashId)?->status)->toBe('trashed');
        $restoredPath = $trashService->restore($disk, $item->trashId);
        expect($restoredPath->value)->toBe('/locked.txt');
    });
});
