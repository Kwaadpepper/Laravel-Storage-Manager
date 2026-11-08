<?php

declare(strict_types=1);

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Action\File\CreateFileAction;
use Kwaadpepper\LaravelStorageManager\Action\File\CreateFileInput;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;
use Kwaadpepper\LaravelStorageManager\Event\FileCreated;
use Kwaadpepper\LaravelStorageManager\Event\FileCreating;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Exception\OperationCancelledException;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;

describe('CreateFileAction', function (): void {
    beforeEach(function (): void {
        Storage::fake('local');
        Config::set('filesystems.disks.local', [
            'driver' => 'local',
        ]);
        Config::set('storage-manager.disks.available', ['local']);
    });

    it('creates a file and dispatches FileCreated', function (): void {
        // GIVEN
        Event::fake([FileCreated::class]);
        $disk   = new Disk('local', 'local');
        $action = resolve(CreateFileAction::class);
        $path   = new Path('/new_file.txt');
        $input  = new CreateFileInput($disk, $path, 'sample text');

        // WHEN
        $action($input);

        // THEN
        expect(Storage::disk('local')->exists('new_file.txt'))->toBeTrue();
        Event::assertDispatched(
            FileCreated::class,
            fn (FileCreated $event): bool => $event->path === '/new_file.txt'
        );
    });

    it('throws FileOperationException when storageGateway put fails', function (): void {
        // GIVEN
        $filesystem = \Mockery::mock(FilesystemAdapter::class);
        $filesystem->shouldReceive('exists')->andReturn(false);
        $filesystem->shouldReceive('directories')->andReturn([]);
        $filesystem->shouldReceive('put')->once()->andReturn(false);
        Storage::set('local', $filesystem);
        $action = resolve(CreateFileAction::class);
        $disk   = new Disk('local', 'local');
        $path   = new Path('/fail.txt');
        $input  = new CreateFileInput($disk, $path);

        // WHEN
        $callback = static function () use ($action, $input): void {
            $action($input);
        };

        // THEN
        expect($callback)->toThrow(FileOperationException::class, FileOperationError::UNKNOWN_ERROR->message());
    });

    it('throws FileOperationException when extension is forbidden', function (): void {
        // GIVEN
        Config::set('storage-manager.upload.allowed_extensions', ['txt', 'pdf']);
        $action = resolve(CreateFileAction::class);
        $disk   = new Disk('local', 'local');
        $path   = new Path('/script.exe');
        $input  = new CreateFileInput($disk, $path);

        // WHEN
        $callback = static function () use ($action, $input): void {
            $action($input);
        };

        // THEN
        expect($callback)->toThrow(FileOperationException::class, FileOperationError::FORBIDDEN_FILE_EXTENSION->message());
    });

    it('throws FileOperationException when file already exists', function (): void {
        // GIVEN
        Storage::disk('local')->put('existing.txt', 'content');
        $action = resolve(CreateFileAction::class);
        $disk   = new Disk('local', 'local');
        $path   = new Path('/existing.txt');
        $input  = new CreateFileInput($disk, $path);

        // WHEN
        $callback = static function () use ($action, $input): void {
            $action($input);
        };

        // THEN
        expect($callback)->toThrow(FileOperationException::class, FileOperationError::FILE_ALREADY_EXISTS->message());
    });

    it('dispatches FileCreating before creating file', function (): void {
        // GIVEN
        Event::fake([FileCreating::class, FileCreated::class]);
        $disk   = new Disk('local', 'local');
        $action = resolve(CreateFileAction::class);
        $path   = new Path('/new_file.txt');
        $input  = new CreateFileInput($disk, $path, 'sample text');

        // WHEN
        $action($input);

        // THEN
        expect(Storage::disk('local')->exists('new_file.txt'))->toBeTrue();
        Event::assertDispatched(
            FileCreating::class,
            fn (FileCreating $event): bool => $event->disk === 'local'
                && $event->path                            === '/new_file.txt'
                && $event->size                            === 11
        );
        Event::assertDispatched(FileCreated::class);
    });

    it('throws OperationCancelledException and prevents file creation when FileCreating is cancelled', function (): void {
        // GIVEN
        Event::listen(FileCreating::class, function (FileCreating $event): void {
            $event->cancel('User quota exceeded');
        });
        $disk   = new Disk('local', 'local');
        $action = resolve(CreateFileAction::class);
        $path   = new Path('/cancelled_file.txt');
        $input  = new CreateFileInput($disk, $path, 'sample text');

        // WHEN
        $callback = static function () use ($action, $input): void {
            $action($input);
        };

        // THEN
        expect($callback)->toThrow(OperationCancelledException::class, 'User quota exceeded');
        expect(Storage::disk('local')->exists('cancelled_file.txt'))->toBeFalse();
    });

    it('propagates exception and prevents file creation when listener throws', function (): void {
        // GIVEN
        Event::listen(FileCreating::class, function (FileCreating $event): void {
            throw new \RuntimeException('DB lock conflict');
        });
        $disk   = new Disk('local', 'local');
        $action = resolve(CreateFileAction::class);
        $path   = new Path('/aborted_file.txt');
        $input  = new CreateFileInput($disk, $path, 'sample text');

        // WHEN
        $callback = static function () use ($action, $input): void {
            $action($input);
        };

        // THEN
        expect($callback)->toThrow(\RuntimeException::class, 'DB lock conflict');
        expect(Storage::disk('local')->exists('aborted_file.txt'))->toBeFalse();
    });
})->covers(CreateFileAction::class);
