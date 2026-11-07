<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Action\File\RenamePathAction;
use Kwaadpepper\LaravelStorageManager\Action\File\RenamePathInput;
use Kwaadpepper\LaravelStorageManager\Event\FileRenamed;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;

describe('RenamePathAction', function (): void {
    beforeEach(function (): void {
        Storage::fake('local');
        Config::set('filesystems.disks.local', [
            'driver' => 'local',
        ]);
        Config::set('storage-manager.disks.available', ['local']);
    });

    it('renames a file and returns new path', function (): void {
        // GIVEN
        Event::fake([FileRenamed::class]);
        Storage::disk('local')->put('old.txt', 'test content');
        $disk   = new Disk('local', 'local');
        $action = resolve(RenamePathAction::class);
        $input  = new RenamePathInput($disk, new Path('/old.txt'), 'new.txt');

        // WHEN
        $result = $action($input);

        // THEN
        expect($result->value)->toBe('/new.txt');
        expect(Storage::disk('local')->exists('new.txt'))->toBeTrue();
        expect(Storage::disk('local')->exists('old.txt'))->toBeFalse();
        Event::assertDispatched(
            FileRenamed::class,
            fn (FileRenamed $event): bool => $event->oldPath === '/old.txt'
                && $event->newPath                           === '/new.txt'
        );
    });
})->covers(RenamePathAction::class);
