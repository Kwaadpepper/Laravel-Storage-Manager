<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;
use Kwaadpepper\LaravelStorageManager\Event\BulkRenamed;
use Kwaadpepper\LaravelStorageManager\Event\DirectoryCopied;
use Kwaadpepper\LaravelStorageManager\Event\DirectoryMoved;
use Kwaadpepper\LaravelStorageManager\Event\DirectoryRenamed;
use Kwaadpepper\LaravelStorageManager\Event\FileCopied;
use Kwaadpepper\LaravelStorageManager\Event\FileMoved;
use Kwaadpepper\LaravelStorageManager\Event\FileRenamed;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\FileTransferService;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;

describe('FileTransferService', function (): void {
    beforeEach(function (): void {
        Storage::fake('local');
        Config::set('filesystems.disks.local', [
            'driver' => 'local',
        ]);
        Config::set('storage-manager.disks.available', ['local']);
        Config::set('storage-manager.upload.allowed_extensions', 'txt,pdf,png,jpg');
    });

    describe('rename', function (): void {
        it('renames a file and dispatches FileRenamed', function (): void {
            // GIVEN
            Event::fake([FileRenamed::class]);
            Storage::disk('local')->put('old.txt', 'hello world');
            $disk    = new Disk('local', 'local');
            $service = resolve(FileTransferService::class);

            // WHEN
            $result = $service->rename($disk, new Path('/old.txt'), 'new.txt');

            // THEN
            expect($result->value)->toBe('/new.txt');
            expect(Storage::disk('local')->exists('new.txt'))->toBeTrue();
            expect(Storage::disk('local')->exists('old.txt'))->toBeFalse();
            Event::assertDispatched(
                FileRenamed::class,
                fn (FileRenamed $event): bool => $event->oldPath === '/old.txt' && $event->newPath === '/new.txt'
            );
        });

        it('renames a directory and dispatches DirectoryRenamed', function (): void {
            // GIVEN
            Event::fake([DirectoryRenamed::class]);
            Storage::disk('local')->makeDirectory('old_dir');
            Storage::disk('local')->put('old_dir/file.txt', 'content');
            $disk    = new Disk('local', 'local');
            $service = resolve(FileTransferService::class);

            // WHEN
            $result = $service->rename($disk, new Path('/old_dir'), 'new_dir');

            // THEN
            expect($result->value)->toBe('/new_dir');
            expect(Storage::disk('local')->exists('new_dir/file.txt'))->toBeTrue();
            expect(Storage::disk('local')->exists('old_dir/file.txt'))->toBeFalse();
            Event::assertDispatched(
                DirectoryRenamed::class,
                fn (DirectoryRenamed $event): bool => $event->oldPath === '/old_dir' && $event->newPath === '/new_dir'
            );
        });

        it('throws when destination already exists', function (): void {
            // GIVEN
            Storage::disk('local')->put('first.txt', 'content 1');
            Storage::disk('local')->put('second.txt', 'content 2');
            $disk    = new Disk('local', 'local');
            $service = resolve(FileTransferService::class);

            // WHEN
            $action = fn (): Path => $service->rename($disk, new Path('/first.txt'), 'second.txt');

            // THEN
            expect($action)->toThrow(FileOperationException::class, FileOperationError::FILE_ALREADY_EXISTS->message());
        });

        it('throws when renaming file to forbidden extension', function (): void {
            // GIVEN
            Storage::disk('local')->put('script.txt', 'echo 1;');
            $disk    = new Disk('local', 'local');
            $service = resolve(FileTransferService::class);

            // WHEN
            $action = fn (): Path => $service->rename($disk, new Path('/script.txt'), 'script.exe');

            // THEN
            expect($action)->toThrow(FileOperationException::class, FileOperationError::FORBIDDEN_FILE_EXTENSION->message());
        });
    });

    describe('copy', function (): void {
        it('copies a file and dispatches FileCopied', function (): void {
            // GIVEN
            Event::fake([FileCopied::class]);
            Storage::disk('local')->put('source.txt', 'copy me');
            Storage::disk('local')->makeDirectory('target');
            $disk    = new Disk('local', 'local');
            $service = resolve(FileTransferService::class);

            // WHEN
            $result = $service->copy($disk, new Path('/source.txt'), new Path('/target'));

            // THEN
            expect($result->value)->toBe('/target/source.txt');
            expect(Storage::disk('local')->exists('source.txt'))->toBeTrue();
            expect(Storage::disk('local')->exists('target/source.txt'))->toBeTrue();
            Event::assertDispatched(
                FileCopied::class,
                fn (FileCopied $event): bool => $event->sourcePath === '/source.txt' && $event->targetPath === '/target/source.txt'
            );
        });

        it('copies a file with auto-renamed destination when conflict exists', function (): void {
            // GIVEN
            Event::fake([FileCopied::class]);
            Storage::disk('local')->put('source.txt', 'first');
            Storage::disk('local')->makeDirectory('target');
            Storage::disk('local')->put('target/source.txt', 'already there');
            $disk    = new Disk('local', 'local');
            $service = resolve(FileTransferService::class);

            // WHEN
            $result = $service->copy($disk, new Path('/source.txt'), new Path('/target'));

            // THEN
            expect($result->value)->toBe('/target/source (copy).txt');
            expect(Storage::disk('local')->exists('target/source.txt'))->toBeTrue();
            expect(Storage::disk('local')->exists('target/source (copy).txt'))->toBeTrue();
            Event::assertDispatched(
                FileCopied::class,
                fn (FileCopied $event): bool => $event->sourcePath === '/source.txt' && $event->targetPath === '/target/source (copy).txt'
            );
        });

        it('copies a directory and dispatches DirectoryCopied', function (): void {
            // GIVEN
            Event::fake([DirectoryCopied::class]);
            Storage::disk('local')->makeDirectory('src_dir');
            Storage::disk('local')->put('src_dir/item.txt', 'item data');
            Storage::disk('local')->makeDirectory('dest_dir');
            $disk    = new Disk('local', 'local');
            $service = resolve(FileTransferService::class);

            // WHEN
            $result = $service->copy($disk, new Path('/src_dir'), new Path('/dest_dir'));

            // THEN
            expect($result->value)->toBe('/dest_dir/src_dir');
            expect(Storage::disk('local')->exists('dest_dir/src_dir/item.txt'))->toBeTrue();
            expect(Storage::disk('local')->exists('src_dir/item.txt'))->toBeTrue();
            Event::assertDispatched(
                DirectoryCopied::class,
                fn (DirectoryCopied $event): bool => $event->sourcePath === '/src_dir' && $event->targetPath === '/dest_dir/src_dir'
            );
        });
    });

    describe('move', function (): void {
        it('moves a file and dispatches FileMoved', function (): void {
            // GIVEN
            Event::fake([FileMoved::class]);
            Storage::disk('local')->put('item.txt', 'item content');
            Storage::disk('local')->makeDirectory('dest');
            $disk    = new Disk('local', 'local');
            $service = resolve(FileTransferService::class);

            // WHEN
            $result = $service->move($disk, new Path('/item.txt'), new Path('/dest'));

            // THEN
            expect($result->value)->toBe('/dest/item.txt');
            expect(Storage::disk('local')->exists('dest/item.txt'))->toBeTrue();
            expect(Storage::disk('local')->exists('item.txt'))->toBeFalse();
            Event::assertDispatched(
                FileMoved::class,
                fn (FileMoved $event): bool => $event->sourcePath === '/item.txt' && $event->targetPath === '/dest/item.txt'
            );
        });

        it('moves a file with collision auto-rename', function (): void {
            // GIVEN
            Event::fake([FileMoved::class]);
            Storage::disk('local')->put('item.txt', 'new data');
            Storage::disk('local')->makeDirectory('dest');
            Storage::disk('local')->put('dest/item.txt', 'old data');
            $disk    = new Disk('local', 'local');
            $service = resolve(FileTransferService::class);

            // WHEN
            $result = $service->move($disk, new Path('/item.txt'), new Path('/dest'));

            // THEN
            expect($result->value)->toBe('/dest/item (copy).txt');
            expect(Storage::disk('local')->exists('dest/item (copy).txt'))->toBeTrue();
            expect(Storage::disk('local')->exists('dest/item.txt'))->toBeTrue();
            expect(Storage::disk('local')->exists('item.txt'))->toBeFalse();
            Event::assertDispatched(
                FileMoved::class,
                fn (FileMoved $event): bool => $event->sourcePath === '/item.txt' && $event->targetPath === '/dest/item (copy).txt'
            );
        });

        it('moves a directory and dispatches DirectoryMoved', function (): void {
            // GIVEN
            Event::fake([DirectoryMoved::class]);
            Storage::disk('local')->makeDirectory('folder');
            Storage::disk('local')->put('folder/sub.txt', 'data');
            Storage::disk('local')->makeDirectory('target');
            $disk    = new Disk('local', 'local');
            $service = resolve(FileTransferService::class);

            // WHEN
            $result = $service->move($disk, new Path('/folder'), new Path('/target'));

            // THEN
            expect($result->value)->toBe('/target/folder');
            expect(Storage::disk('local')->exists('target/folder/sub.txt'))->toBeTrue();
            expect(Storage::disk('local')->exists('folder/sub.txt'))->toBeFalse();
            Event::assertDispatched(
                DirectoryMoved::class,
                fn (DirectoryMoved $event): bool => $event->sourcePath === '/folder' && $event->targetPath === '/target/folder'
            );
        });
    });

    describe('bulkRename', function (): void {
        it('renames multiple files without overlap and dispatches BulkRenamed', function (): void {
            // GIVEN
            Event::fake([BulkRenamed::class]);
            Storage::disk('local')->put('file_a.txt', 'alpha');
            Storage::disk('local')->put('file_b.txt', 'beta');
            $disk    = new Disk('local', 'local');
            $service = resolve(FileTransferService::class);

            // WHEN
            $result = $service->bulkRename($disk, [
                ['from' => new Path('/file_a.txt'), 'to' => 'renamed_a.txt'],
                ['from' => new Path('/file_b.txt'), 'to' => 'renamed_b.txt'],
            ]);

            // THEN
            expect($result)->toHaveCount(2);
            expect(Storage::disk('local')->exists('renamed_a.txt'))->toBeTrue();
            expect(Storage::disk('local')->exists('renamed_b.txt'))->toBeTrue();
            expect(Storage::disk('local')->exists('file_a.txt'))->toBeFalse();
            expect(Storage::disk('local')->exists('file_b.txt'))->toBeFalse();
            Event::assertDispatched(BulkRenamed::class);
        });

        it('swaps file names with overlap using temporary files', function (): void {
            // GIVEN
            Event::fake([BulkRenamed::class]);
            Storage::disk('local')->put('first.txt', 'content of first');
            Storage::disk('local')->put('second.txt', 'content of second');
            $disk    = new Disk('local', 'local');
            $service = resolve(FileTransferService::class);

            // WHEN
            $result = $service->bulkRename($disk, [
                ['from' => new Path('/first.txt'), 'to' => 'second.txt'],
                ['from' => new Path('/second.txt'), 'to' => 'first.txt'],
            ]);

            // THEN
            expect($result)->toHaveCount(2);
            expect(Storage::disk('local')->get('second.txt'))->toBe('content of first');
            expect(Storage::disk('local')->get('first.txt'))->toBe('content of second');
            Event::assertDispatched(BulkRenamed::class);
        });

        it('throws when multiple items in batch target the same destination', function (): void {
            // GIVEN
            Storage::disk('local')->put('a.txt', 'a');
            Storage::disk('local')->put('b.txt', 'b');
            $disk    = new Disk('local', 'local');
            $service = resolve(FileTransferService::class);

            // WHEN
            $action = fn (): array => $service->bulkRename($disk, [
                ['from' => new Path('/a.txt'), 'to' => 'same.txt'],
                ['from' => new Path('/b.txt'), 'to' => 'same.txt'],
            ]);

            // THEN
            expect($action)->toThrow(FileOperationException::class, FileOperationError::FILE_ALREADY_EXISTS->message());
        });
    });
})->covers(FileTransferService::class);
