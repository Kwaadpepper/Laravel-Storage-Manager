<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Action\File\CreateDirectoryAction;
use Kwaadpepper\LaravelStorageManager\Action\File\CreateDirectoryInput;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;
use Kwaadpepper\LaravelStorageManager\Event\DirectoryCreated;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;

describe('CreateDirectoryAction', function (): void {
    beforeEach(function (): void {
        Storage::fake('local');
        Config::set('filesystems.disks.local', [
            'driver' => 'local',
        ]);
        Config::set('storage-manager.disks.available', ['local']);
    });

    it('creates a directory and dispatches DirectoryCreated', function (): void {
        // GIVEN
        Event::fake([DirectoryCreated::class]);
        $disk   = new Disk('local', 'local');
        $action = resolve(CreateDirectoryAction::class);
        $path   = new Path('/new_folder');
        $input  = new CreateDirectoryInput($disk, $path);

        // WHEN
        $action($input);

        // THEN
        expect(Storage::disk('local')->exists('new_folder'))->toBeTrue();
        Event::assertDispatched(
            DirectoryCreated::class,
            fn (DirectoryCreated $event): bool => $event->path === '/new_folder'
        );
    });

    it('throws FileOperationException when directory already exists', function (): void {
        // GIVEN
        Storage::disk('local')->makeDirectory('existing_folder');
        $disk   = new Disk('local', 'local');
        $action = resolve(CreateDirectoryAction::class);
        $path   = new Path('/existing_folder');
        $input  = new CreateDirectoryInput($disk, $path);

        // WHEN
        $callback = static function () use ($action, $input): void {
            $action($input);
        };

        // THEN
        expect($callback)->toThrow(FileOperationException::class, FileOperationError::DIRECTORY_ALREADY_EXISTS->message());
    });
})->covers(CreateDirectoryAction::class);
