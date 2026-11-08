<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Event\FileRestoring;
use Kwaadpepper\LaravelStorageManager\Event\FileTrashing;
use Kwaadpepper\LaravelStorageManager\Event\TrashEmptying;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Exception\OperationCancelledException;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Service\TrashService;

describe('TrashService', function (): void {
    beforeEach(function (): void {
        CarbonImmutable::setTestNow('2026-01-01 12:00:00');
        Storage::fake('local');
        Config::set('storage-manager.enabled', true);
        Config::set('storage-manager.trash.enabled', true);
        Config::set('storage-manager.trash.folder', '.trash');
        Config::set('storage-manager.trash.retention_days', 30);
        App::forgetInstance(StorageManagerConfig::class);
    });

    afterEach(function (): void {
        CarbonImmutable::setTestNow();
    });

    it('moves a file to trash and creates metadata', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $storage->put('test-file.txt', 'hello world');
        $trashService = resolve(TrashService::class);
        assert($trashService instanceof TrashService);
        $disk = new Disk('local', 'local');

        // WHEN
        $item = $trashService->moveToTrash($disk, new Path('/test-file.txt'));

        // THEN
        expect($item->name)->toBe('test-file.txt')
            ->and($item->type)->toBe('file')
            ->and($item->size)->toBe(11)
            ->and($item->originalPath->value)->toBe('/test-file.txt');

        $storage->assertMissing('test-file.txt');
        $storage->assertExists(".trash/meta/{$item->trashId}.json");
        $storage->assertExists(".trash/files/{$item->trashId}_test-file.txt");
    });

    it('moves a directory to trash with its files', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $storage->makeDirectory('my-folder');
        $storage->put('my-folder/file1.txt', 'abc');
        $storage->put('my-folder/file2.txt', '12345');
        $trashService = resolve(TrashService::class);
        assert($trashService instanceof TrashService);
        $disk = new Disk('local', 'local');

        // WHEN
        $item = $trashService->moveToTrash($disk, new Path('/my-folder'));

        // THEN
        expect($item->name)->toBe('my-folder')
            ->and($item->type)->toBe('directory')
            ->and($item->size)->toBe(8);

        $storage->assertMissing('my-folder');
        $storage->assertExists(".trash/meta/{$item->trashId}.json");
        $storage->assertExists(".trash/files/{$item->trashId}_my-folder/file1.txt");
    });

    it('throws when trying to move non-existent path to trash', function (): void {
        // GIVEN
        $trashService = resolve(TrashService::class);
        assert($trashService instanceof TrashService);
        $disk = new Disk('local', 'local');

        // WHEN
        $action = fn (): mixed => $trashService->moveToTrash($disk, new Path('/non-existent.txt'));

        // THEN
        expect($action)->toThrow(FileOperationException::class);
    });

    it('throws when trash is disabled in config', function (): void {
        // GIVEN
        Config::set('storage-manager.trash.enabled', false);
        App::forgetInstance(StorageManagerConfig::class);

        $storage = Storage::disk('local');
        $storage->put('test.txt', 'abc');
        $trashService = resolve(TrashService::class);
        assert($trashService instanceof TrashService);
        $disk = new Disk('local', 'local');

        // WHEN
        $action = fn (): mixed => $trashService->moveToTrash($disk, new Path('/test.txt'));

        // THEN
        expect($action)->toThrow(FileOperationException::class);
    });

    it('throws when trash is disabled on disk', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $storage->put('test.txt', 'abc');
        $trashService = resolve(TrashService::class);
        assert($trashService instanceof TrashService);
        $disk = new Disk('local', 'local', trash: false);

        // WHEN
        $action = fn (): mixed => $trashService->moveToTrash($disk, new Path('/test.txt'));

        // THEN
        expect($action)->toThrow(FileOperationException::class);
    });

    it('throws when trying to trash root path or inside trash folder', function (): void {
        // GIVEN
        $trashService = resolve(TrashService::class);
        assert($trashService instanceof TrashService);
        $disk = new Disk('local', 'local');

        // WHEN
        $action = fn (): mixed => $trashService->moveToTrash($disk, new Path('/'));

        // THEN
        expect($action)->toThrow(FileOperationException::class);
    });

    it('lists trashed items sorted by deletion date descending', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $storage->put('first.txt', '1');
        $storage->put('second.txt', '22');
        $trashService = resolve(TrashService::class);
        assert($trashService instanceof TrashService);
        $disk = new Disk('local', 'local');

        $item1 = $trashService->moveToTrash($disk, new Path('/first.txt'));
        $item2 = $trashService->moveToTrash($disk, new Path('/second.txt'));

        $metaPath = ".trash/meta/{$item2->trashId}.json";
        $metaData = json_decode((string) $storage->get($metaPath), true);
        assert(is_array($metaData));
        $metaData['deletedAt'] = CarbonImmutable::now()->addMinute()->toIso8601String();
        $storage->put($metaPath, (string) json_encode($metaData));

        // WHEN
        $list = $trashService->list($disk);

        // THEN
        expect($list)->toHaveCount(2)
            ->and($list[0]->trashId)->toBe($item2->trashId)
            ->and($list[1]->trashId)->toBe($item1->trashId);
    });

    it('restores a file to its original path', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $storage->put('docs/report.pdf', 'pdf-data');
        $trashService = resolve(TrashService::class);
        assert($trashService instanceof TrashService);
        $disk = new Disk('local', 'local');

        $item = $trashService->moveToTrash($disk, new Path('/docs/report.pdf'));
        $storage->deleteDirectory('docs');

        // WHEN
        $restoredPath = $trashService->restore($disk, $item->trashId);

        // THEN
        expect($restoredPath->value)->toBe('/docs/report.pdf');
        $storage->assertExists('docs/report.pdf');
        $storage->assertMissing(".trash/meta/{$item->trashId}.json");
        $storage->assertMissing(".trash/files/{$item->trashId}_report.pdf");
    });

    it('handles duplicate conflict on restore by auto-renaming', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $storage->put('file.txt', 'old version');
        $trashService = resolve(TrashService::class);
        assert($trashService instanceof TrashService);
        $disk = new Disk('local', 'local');

        $item = $trashService->moveToTrash($disk, new Path('/file.txt'));
        $storage->put('file.txt', 'new version');

        // WHEN
        $restoredPath = $trashService->restore($disk, $item->trashId);

        // THEN
        expect($restoredPath->value)->toBe('/file (restored).txt');
        $storage->assertExists('file.txt');
        $storage->assertExists('file (restored).txt');
        expect($storage->get('file (restored).txt'))->toBe('old version');
    });

    it('purges a specific trashed item permanently', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $storage->put('purge-me.txt', 'to-be-purged');
        $trashService = resolve(TrashService::class);
        assert($trashService instanceof TrashService);
        $disk = new Disk('local', 'local');

        $item = $trashService->moveToTrash($disk, new Path('/purge-me.txt'));

        // WHEN
        $trashService->purge($disk, $item->trashId);

        // THEN
        expect($trashService->getItem($disk, $item->trashId))->toBeNull();
        $storage->assertMissing(".trash/meta/{$item->trashId}.json");
        $storage->assertMissing(".trash/files/{$item->trashId}_purge-me.txt");
    });

    it('empties the entire trash for a disk', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $storage->put('a.txt', 'a');
        $storage->put('b.txt', 'b');
        $trashService = resolve(TrashService::class);
        assert($trashService instanceof TrashService);
        $disk = new Disk('local', 'local');

        $trashService->moveToTrash($disk, new Path('/a.txt'));
        $trashService->moveToTrash($disk, new Path('/b.txt'));

        // WHEN
        $count = $trashService->emptyTrash($disk);

        // THEN
        expect($count)->toBe(2)
            ->and($trashService->list($disk))->toBeEmpty();
    });

    it('prunes expired trashed items based on retention days', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $storage->put('old.txt', 'old');
        $storage->put('recent.txt', 'recent');
        $trashService = resolve(TrashService::class);
        assert($trashService instanceof TrashService);
        $disk = new Disk('local', 'local');

        $oldItem    = $trashService->moveToTrash($disk, new Path('/old.txt'));
        $recentItem = $trashService->moveToTrash($disk, new Path('/recent.txt'));

        $metaPath = ".trash/meta/{$oldItem->trashId}.json";
        $metaData = json_decode((string) $storage->get($metaPath), true);
        assert(is_array($metaData));
        $metaData['deletedAt'] = CarbonImmutable::now()->subDays(40)->toIso8601String();
        $storage->put($metaPath, (string) json_encode($metaData));

        // WHEN
        $purgedCount = $trashService->pruneExpired($disk, 30);

        // THEN
        expect($purgedCount)->toBe(1)
            ->and($trashService->getItem($disk, $oldItem->trashId))->toBeNull()
            ->and($trashService->getItem($disk, $recentItem->trashId))->not->toBeNull();
    });

    it('reports restoring status for a locked trash item', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $storage->put('locked.txt', 'content');
        $trashService = resolve(TrashService::class);
        assert($trashService instanceof TrashService);
        $disk = new Disk('local', 'local');

        $item = $trashService->moveToTrash($disk, new Path('/locked.txt'));
        $storage->put(".trash/meta/{$item->trashId}.lock", 'restoring');

        // WHEN
        $itemStatus = $trashService->getItem($disk, $item->trashId)?->status;
        $list       = $trashService->list($disk);

        // THEN
        expect($itemStatus)->toBe('restoring');
        expect($list[0]->status)->toBe('restoring');
    });

    it('throws exception when attempting to restore a locked trash item', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $storage->put('locked.txt', 'content');
        $trashService = resolve(TrashService::class);
        assert($trashService instanceof TrashService);
        $disk = new Disk('local', 'local');

        $item = $trashService->moveToTrash($disk, new Path('/locked.txt'));
        $storage->put(".trash/meta/{$item->trashId}.lock", 'restoring');

        // WHEN
        $action = fn (): mixed => $trashService->restore($disk, $item->trashId);

        // THEN
        expect($action)->toThrow(FileOperationException::class);
    });

    it('throws exception when attempting to purge a locked trash item', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $storage->put('locked.txt', 'content');
        $trashService = resolve(TrashService::class);
        assert($trashService instanceof TrashService);
        $disk = new Disk('local', 'local');

        $item = $trashService->moveToTrash($disk, new Path('/locked.txt'));
        $storage->put(".trash/meta/{$item->trashId}.lock", 'restoring');

        // WHEN
        $action = fn (): mixed => $trashService->purge($disk, $item->trashId);

        // THEN
        expect($action)->toThrow(FileOperationException::class);
    });

    it('throws exception when attempting to empty trash with locked items', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $storage->put('locked.txt', 'content');
        $trashService = resolve(TrashService::class);
        assert($trashService instanceof TrashService);
        $disk = new Disk('local', 'local');

        $item = $trashService->moveToTrash($disk, new Path('/locked.txt'));
        $storage->put(".trash/meta/{$item->trashId}.lock", 'restoring');

        // WHEN
        $action = fn (): mixed => $trashService->emptyTrash($disk);

        // THEN
        expect($action)->toThrow(FileOperationException::class);
    });

    it('allows restoring an item after removing its lock file', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $storage->put('locked.txt', 'content');
        $trashService = resolve(TrashService::class);
        assert($trashService instanceof TrashService);
        $disk = new Disk('local', 'local');

        $item = $trashService->moveToTrash($disk, new Path('/locked.txt'));
        $storage->put(".trash/meta/{$item->trashId}.lock", 'restoring');
        $storage->delete(".trash/meta/{$item->trashId}.lock");

        // WHEN
        $status       = $trashService->getItem($disk, $item->trashId)?->status;
        $restoredPath = $trashService->restore($disk, $item->trashId);

        // THEN
        expect($status)->toBe('trashed');
        expect($restoredPath->value)->toBe('/locked.txt');
    });

    it('throws FileOperationException when moveToTrash is called on a read-only disk', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $storage->put('test-file.txt', 'hello');
        $trashService = resolve(TrashService::class);
        assert($trashService instanceof TrashService);
        $disk = new Disk('local', 'local', false, false, true); // readOnly = true

        // WHEN
        $action = fn (): mixed => $trashService->moveToTrash($disk, new Path('/test-file.txt'));

        // THEN
        expect($action)->toThrow(FileOperationException::class);
    });

    it('throws FileOperationException when restore is called on a read-only disk', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $storage->put('restore-test.txt', 'content');
        $writableDisk = new Disk('local', 'local');
        $trashService = resolve(TrashService::class);
        assert($trashService instanceof TrashService);

        $item = $trashService->moveToTrash($writableDisk, new Path('/restore-test.txt'));

        $readOnlyDisk = new Disk('local', 'local', false, false, true);

        // WHEN
        $action = fn (): mixed => $trashService->restore($readOnlyDisk, $item->trashId);

        // THEN
        expect($action)->toThrow(FileOperationException::class);
    });

    it('throws FileOperationException when purge is called on a read-only disk', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $storage->put('purge-test.txt', 'content');
        $writableDisk = new Disk('local', 'local');
        $trashService = resolve(TrashService::class);
        assert($trashService instanceof TrashService);

        $item = $trashService->moveToTrash($writableDisk, new Path('/purge-test.txt'));

        $readOnlyDisk = new Disk('local', 'local', false, false, true);

        // WHEN
        $action = fn (): mixed => $trashService->purge($readOnlyDisk, $item->trashId);

        // THEN
        expect($action)->toThrow(FileOperationException::class);
    });

    it('throws FileOperationException when emptyTrash is called on a read-only disk', function (): void {
        // GIVEN
        $trashService = resolve(TrashService::class);
        assert($trashService instanceof TrashService);
        $disk = new Disk('local', 'local', false, false, true); // readOnly = true

        // WHEN
        $action = fn (): mixed => $trashService->emptyTrash($disk);

        // THEN
        expect($action)->toThrow(FileOperationException::class);
    });

    it('throws OperationCancelledException and prevents moving to trash when FileTrashing is cancelled', function (): void {
        // GIVEN
        Event::listen(FileTrashing::class, function (FileTrashing $event): void {
            $event->cancel('Trashing disallowed');
        });
        $storage = Storage::disk('local');
        $storage->put('keep_safe.txt', 'do not trash');
        $trashService = resolve(TrashService::class);
        assert($trashService instanceof TrashService);
        $disk = new Disk('local', 'local');

        // WHEN
        $action = fn (): mixed => $trashService->moveToTrash($disk, new Path('/keep_safe.txt'));

        // THEN
        expect($action)->toThrow(OperationCancelledException::class, 'Trashing disallowed');
        $storage->assertExists('keep_safe.txt');
    });

    it('throws OperationCancelledException and prevents restore when FileRestoring is cancelled', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $storage->put('restore_file.txt', 'important data');
        $trashService = resolve(TrashService::class);
        assert($trashService instanceof TrashService);
        $disk = new Disk('local', 'local');
        $item = $trashService->moveToTrash($disk, new Path('/restore_file.txt'));

        Event::listen(FileRestoring::class, function (FileRestoring $event): void {
            $event->cancel('Restore disallowed');
        });

        // WHEN
        $action = fn (): mixed => $trashService->restore($disk, $item->trashId);

        // THEN
        expect($action)->toThrow(OperationCancelledException::class, 'Restore disallowed');
        $storage->assertMissing('restore_file.txt');
    });

    it('throws OperationCancelledException and prevents emptyTrash when TrashEmptying is cancelled', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $storage->put('trash_me.txt', 'data');
        $trashService = resolve(TrashService::class);
        assert($trashService instanceof TrashService);
        $disk = new Disk('local', 'local');
        $trashService->moveToTrash($disk, new Path('/trash_me.txt'));

        Event::listen(TrashEmptying::class, function (TrashEmptying $event): void {
            $event->cancel('Emptying trash is locked');
        });

        // WHEN
        $action = fn (): mixed => $trashService->emptyTrash($disk);

        // THEN
        expect($action)->toThrow(OperationCancelledException::class, 'Emptying trash is locked');
        expect($trashService->list($disk))->toHaveCount(1);
    });
})->covers(TrashService::class);
