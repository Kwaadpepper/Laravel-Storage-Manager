<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Action\Archive\ZipAction;
use Kwaadpepper\LaravelStorageManager\Action\Archive\ZipInput;
use Kwaadpepper\LaravelStorageManager\Event\ZipCreated;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;

describe('ZipAction', function (): void {
    beforeEach(function (): void {
        Storage::fake('local');
        Config::set('filesystems.disks.local', [
            'driver' => 'local',
        ]);
        Config::set('storage-manager.disks.available', ['local']);
        Config::set('storage-manager.upload.allowed_extensions', 'txt,zip,pdf');
    });

    it('creates zip archive from paths and dispatches ZipCreated', function (): void {
        // GIVEN
        Event::fake([ZipCreated::class]);
        Storage::disk('local')->put('file1.txt', 'Content 1');
        Storage::disk('local')->put('file2.txt', 'Content 2');
        $disk   = new Disk('local', 'local');
        $action = resolve(ZipAction::class);
        $paths  = [new Path('/file1.txt'), new Path('/file2.txt')];
        $dest   = new Path('/bundle.zip');
        $input  = new ZipInput($disk, $paths, $dest);

        // WHEN
        $result = $action($input);

        // THEN
        expect($result->value)->toBe('/bundle.zip');
        expect(Storage::disk('local')->exists('bundle.zip'))->toBeTrue();
        Event::assertDispatched(
            ZipCreated::class,
            fn (ZipCreated $event): bool => $event->path === '/bundle.zip'
        );
    });
})->covers(ZipAction::class);
