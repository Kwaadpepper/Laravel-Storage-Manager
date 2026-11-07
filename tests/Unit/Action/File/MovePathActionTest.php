<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Action\File\MovePathAction;
use Kwaadpepper\LaravelStorageManager\Action\File\MovePathInput;
use Kwaadpepper\LaravelStorageManager\Event\DirectoryMoved;
use Kwaadpepper\LaravelStorageManager\Event\FileMoved;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;

describe('MovePathAction', function (): void {
    beforeEach(function (): void {
        Storage::fake('local');
        Config::set('filesystems.disks.local', [
            'driver' => 'local',
        ]);
        Config::set('storage-manager.disks.available', ['local']);
    });

    it('moves a file and dispatches FileMoved with exact target path', function (): void {
        // GIVEN
        Event::fake([FileMoved::class]);
        Storage::disk('local')->put('document.txt', 'test content');
        Storage::disk('local')->makeDirectory('sub');
        $disk   = new Disk('local', 'local');
        $action = resolve(MovePathAction::class);
        $input  = new MovePathInput($disk, new Path('/document.txt'), new Path('/sub'));

        // WHEN
        $result = $action($input);

        // THEN
        expect($result->value)->toBe('/sub/document.txt');
        expect(Storage::disk('local')->exists('sub/document.txt'))->toBeTrue();
        expect(Storage::disk('local')->exists('document.txt'))->toBeFalse();
        Event::assertDispatched(
            FileMoved::class,
            fn (FileMoved $event): bool => $event->sourcePath === '/document.txt'
                && $event->targetPath                         === '/sub/document.txt'
        );
    });

    it('moves a file and dispatches FileMoved with auto-renamed target path when destination conflicts', function (): void {
        // GIVEN
        Event::fake([FileMoved::class]);
        Storage::disk('local')->put('document.txt', 'new content');
        Storage::disk('local')->makeDirectory('sub');
        Storage::disk('local')->put('sub/document.txt', 'existing content');
        $disk   = new Disk('local', 'local');
        $action = resolve(MovePathAction::class);
        $input  = new MovePathInput($disk, new Path('/document.txt'), new Path('/sub'));

        // WHEN
        $result = $action($input);

        // THEN
        expect($result->value)->toBe('/sub/document (copy).txt');
        expect(Storage::disk('local')->exists('sub/document (copy).txt'))->toBeTrue();
        expect(Storage::disk('local')->exists('document.txt'))->toBeFalse();
        Event::assertDispatched(
            FileMoved::class,
            fn (FileMoved $event): bool => $event->sourcePath === '/document.txt'
                && $event->targetPath                         === '/sub/document (copy).txt'
        );
    });

    it('moves a file and increments duplicate suffix when first copy already exists', function (): void {
        // GIVEN
        Event::fake([FileMoved::class]);
        Storage::disk('local')->put('document.txt', 'new content');
        Storage::disk('local')->makeDirectory('sub');
        Storage::disk('local')->put('sub/document.txt', 'existing content');
        Storage::disk('local')->put('sub/document (copy).txt', 'copy 1 content');
        $disk   = new Disk('local', 'local');
        $action = resolve(MovePathAction::class);
        $input  = new MovePathInput($disk, new Path('/document.txt'), new Path('/sub'));

        // WHEN
        $result = $action($input);

        // THEN
        expect($result->value)->toBe('/sub/document (copy 2).txt');
        expect(Storage::disk('local')->exists('sub/document (copy 2).txt'))->toBeTrue();
        expect(Storage::disk('local')->exists('document.txt'))->toBeFalse();
        Event::assertDispatched(
            FileMoved::class,
            fn (FileMoved $event): bool => $event->sourcePath === '/document.txt'
                && $event->targetPath                         === '/sub/document (copy 2).txt'
        );
    });

    it('moves a directory and dispatches DirectoryMoved with exact target path', function (): void {
        // GIVEN
        Event::fake([DirectoryMoved::class]);
        Storage::disk('local')->makeDirectory('source_dir');
        Storage::disk('local')->put('source_dir/file.txt', 'inner content');
        Storage::disk('local')->makeDirectory('target_dir');
        $disk   = new Disk('local', 'local');
        $action = resolve(MovePathAction::class);
        $input  = new MovePathInput($disk, new Path('/source_dir'), new Path('/target_dir'));

        // WHEN
        $result = $action($input);

        // THEN
        expect($result->value)->toBe('/target_dir/source_dir');
        expect(Storage::disk('local')->exists('target_dir/source_dir/file.txt'))->toBeTrue();
        expect(Storage::disk('local')->exists('source_dir'))->toBeFalse();
        Event::assertDispatched(
            DirectoryMoved::class,
            fn (DirectoryMoved $event): bool => $event->sourcePath === '/source_dir'
                && $event->targetPath                              === '/target_dir/source_dir'
        );
    });

    it('moves a directory and dispatches DirectoryMoved with auto-renamed target path when destination conflicts', function (): void {
        // GIVEN
        Event::fake([DirectoryMoved::class]);
        Storage::disk('local')->makeDirectory('source_dir');
        Storage::disk('local')->put('source_dir/file.txt', 'inner content');
        Storage::disk('local')->makeDirectory('target_dir');
        Storage::disk('local')->makeDirectory('target_dir/source_dir');
        $disk   = new Disk('local', 'local');
        $action = resolve(MovePathAction::class);
        $input  = new MovePathInput($disk, new Path('/source_dir'), new Path('/target_dir'));

        // WHEN
        $result = $action($input);

        // THEN
        expect($result->value)->toBe('/target_dir/source_dir (copy)');
        expect(Storage::disk('local')->exists('target_dir/source_dir (copy)/file.txt'))->toBeTrue();
        expect(Storage::disk('local')->exists('source_dir'))->toBeFalse();
        Event::assertDispatched(
            DirectoryMoved::class,
            fn (DirectoryMoved $event): bool => $event->sourcePath === '/source_dir'
                && $event->targetPath                              === '/target_dir/source_dir (copy)'
        );
    });
})->covers(MovePathAction::class);
