<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Action\File\DeletePathAction;
use Kwaadpepper\LaravelStorageManager\Action\File\DeletePathInput;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;
use Kwaadpepper\LaravelStorageManager\Event\DirectoryDeleted;
use Kwaadpepper\LaravelStorageManager\Event\DirectoryDeleting;
use Kwaadpepper\LaravelStorageManager\Event\FileDeleted;
use Kwaadpepper\LaravelStorageManager\Event\FileDeleting;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Exception\OperationCancelledException;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;

describe('DeletePathAction', function (): void {
    beforeEach(function (): void {
        Storage::fake('local');
        Config::set('filesystems.disks.local', [
            'driver' => 'local',
        ]);
        Config::set('storage-manager.disks.available', ['local']);
        Config::set('storage-manager.trash.enabled', false);
        \App::forgetInstance(StorageManagerConfig::class);
    });

    it('deletes a file and dispatches FileDeleted', function (): void {
        // GIVEN
        Event::fake([FileDeleted::class]);
        Storage::disk('local')->put('file_to_delete.txt', 'content');
        $disk   = new Disk('local', 'local');
        $action = resolve(DeletePathAction::class);
        $input  = new DeletePathInput($disk, new Path('/file_to_delete.txt'));

        // WHEN
        $result = $action($input);

        // THEN
        expect($result)->toBeNull();
        expect(Storage::disk('local')->exists('file_to_delete.txt'))->toBeFalse();
        Event::assertDispatched(
            FileDeleted::class,
            fn (FileDeleted $event): bool => $event->path === '/file_to_delete.txt'
        );
    });

    it('deletes an empty directory and dispatches DirectoryDeleted', function (): void {
        // GIVEN
        Event::fake([DirectoryDeleted::class]);
        Storage::disk('local')->makeDirectory('empty_dir');
        $disk   = new Disk('local', 'local');
        $action = resolve(DeletePathAction::class);
        $input  = new DeletePathInput($disk, new Path('/empty_dir'));

        // WHEN
        $result = $action($input);

        // THEN
        expect($result)->toBeNull();
        expect(Storage::disk('local')->exists('empty_dir'))->toBeFalse();
        Event::assertDispatched(
            DirectoryDeleted::class,
            fn (DirectoryDeleted $event): bool => $event->path === '/empty_dir'
        );
    });

    it('throws FileOperationException when deleting root path', function (): void {
        // GIVEN
        $disk   = new Disk('local', 'local');
        $action = resolve(DeletePathAction::class);
        $input  = new DeletePathInput($disk, new Path('/'));

        // WHEN
        $callback = fn (): mixed => $action($input);

        // THEN
        expect($callback)->toThrow(FileOperationException::class, FileOperationError::INVALID_PATH->message());
    });

    it('throws FileOperationException when deleting non-empty directory without force', function (): void {
        // GIVEN
        Storage::disk('local')->makeDirectory('folder');
        Storage::disk('local')->put('folder/file.txt', 'content');
        $disk   = new Disk('local', 'local');
        $action = resolve(DeletePathAction::class);
        $input  = new DeletePathInput($disk, new Path('/folder'), force: false);

        // WHEN
        $callback = fn (): mixed => $action($input);

        // THEN
        expect($callback)->toThrow(FileOperationException::class, FileOperationError::DIRECTORY_NOT_EMPTY->message());
    });

    it('deletes non-empty directory when force is true', function (): void {
        // GIVEN
        Event::fake([DirectoryDeleted::class]);
        Storage::disk('local')->makeDirectory('folder');
        Storage::disk('local')->put('folder/file.txt', 'content');
        $disk   = new Disk('local', 'local');
        $action = resolve(DeletePathAction::class);
        $input  = new DeletePathInput($disk, new Path('/folder'), force: true);

        // WHEN
        $result = $action($input);

        // THEN
        expect($result)->toBeNull();
        expect(Storage::disk('local')->exists('folder'))->toBeFalse();
    });

    it('moves to trash when trash is enabled and force is false', function (): void {
        // GIVEN
        Config::set('storage-manager.trash.enabled', true);
        \App::forgetInstance(StorageManagerConfig::class);
        Storage::disk('local')->put('trashed.txt', 'content');
        $disk   = new Disk('local', 'local');
        $action = resolve(DeletePathAction::class);
        $input  = new DeletePathInput($disk, new Path('/trashed.txt'), force: false);

        // WHEN
        $result = $action($input);

        // THEN
        expect($result)->not->toBeNull();
        expect($result->originalPath->value)->toBe('/trashed.txt');
    });

    it('dispatches FileDeleting and prevents file deletion when FileDeleting is cancelled', function (): void {
        // GIVEN
        Event::listen(FileDeleting::class, function (FileDeleting $event): void {
            $event->cancel('File is locked by another process');
        });
        Storage::disk('local')->put('locked_file.txt', 'content');
        $disk   = new Disk('local', 'local');
        $action = resolve(DeletePathAction::class);
        $input  = new DeletePathInput($disk, new Path('/locked_file.txt'));

        // WHEN
        $callback = static function () use ($action, $input): void {
            $action($input);
        };

        // THEN
        expect($callback)->toThrow(OperationCancelledException::class, 'File is locked by another process');
        expect(Storage::disk('local')->exists('locked_file.txt'))->toBeTrue();
    });

    it('dispatches DirectoryDeleting and prevents directory deletion when DirectoryDeleting is cancelled', function (): void {
        // GIVEN
        Event::listen(DirectoryDeleting::class, function (DirectoryDeleting $event): void {
            $event->cancel('Directory cannot be deleted');
        });
        Storage::disk('local')->makeDirectory('locked_dir');
        $disk   = new Disk('local', 'local');
        $action = resolve(DeletePathAction::class);
        $input  = new DeletePathInput($disk, new Path('/locked_dir'));

        // WHEN
        $callback = static function () use ($action, $input): void {
            $action($input);
        };

        // THEN
        expect($callback)->toThrow(OperationCancelledException::class, 'Directory cannot be deleted');
        expect(Storage::disk('local')->exists('locked_dir'))->toBeTrue();
    });
})->covers(DeletePathAction::class);
