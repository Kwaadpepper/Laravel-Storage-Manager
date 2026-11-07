<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Action\File\CopyPathAction;
use Kwaadpepper\LaravelStorageManager\Action\File\CopyPathInput;
use Kwaadpepper\LaravelStorageManager\Event\FileCopied;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;

describe('CopyPathAction', function (): void {
    beforeEach(function (): void {
        Storage::fake('local');
        Config::set('filesystems.disks.local', [
            'driver' => 'local',
        ]);
        Config::set('storage-manager.disks.available', ['local']);
    });

    it('copies a file to destination and dispatches FileCopied', function (): void {
        // GIVEN
        Event::fake([FileCopied::class]);
        Storage::disk('local')->put('document.txt', 'test content');
        Storage::disk('local')->makeDirectory('backup');
        $disk   = new Disk('local', 'local');
        $action = resolve(CopyPathAction::class);
        $input  = new CopyPathInput($disk, new Path('/document.txt'), new Path('/backup'));

        // WHEN
        $result = $action($input);

        // THEN
        expect($result->value)->toBe('/backup/document.txt');
        expect(Storage::disk('local')->exists('backup/document.txt'))->toBeTrue();
        expect(Storage::disk('local')->exists('document.txt'))->toBeTrue();
        Event::assertDispatched(
            FileCopied::class,
            fn (FileCopied $event): bool => $event->sourcePath === '/document.txt'
                && $event->targetPath                          === '/backup/document.txt'
        );
    });
})->covers(CopyPathAction::class);
