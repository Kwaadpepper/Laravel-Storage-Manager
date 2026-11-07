<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Action\File\BulkRenameAction;
use Kwaadpepper\LaravelStorageManager\Action\File\BulkRenameInput;
use Kwaadpepper\LaravelStorageManager\Event\BulkRenamed;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;

describe('BulkRenameAction', function (): void {
    beforeEach(function (): void {
        Storage::fake('local');
        Config::set('filesystems.disks.local', [
            'driver' => 'local',
        ]);
        Config::set('storage-manager.disks.available', ['local']);
        Config::set('storage-manager.upload.allowed_extensions', 'txt,pdf');
    });

    it('renames multiple files and dispatches BulkRenamed', function (): void {
        // GIVEN
        Event::fake([BulkRenamed::class]);
        Storage::disk('local')->put('a.txt', 'file a');
        Storage::disk('local')->put('b.txt', 'file b');
        $disk    = new Disk('local', 'local');
        $action  = resolve(BulkRenameAction::class);
        $renames = [
            ['from' => new Path('/a.txt'), 'to' => 'a_renamed.txt'],
            ['from' => new Path('/b.txt'), 'to' => 'b_renamed.txt'],
        ];
        $input = new BulkRenameInput($disk, $renames);

        // WHEN
        $result = $action($input);

        // THEN
        expect($result)->toBe([
            ['from' => '/a.txt', 'to' => '/a_renamed.txt'],
            ['from' => '/b.txt', 'to' => '/b_renamed.txt'],
        ]);
        expect(Storage::disk('local')->exists('a_renamed.txt'))->toBeTrue();
        expect(Storage::disk('local')->exists('b_renamed.txt'))->toBeTrue();
        expect(Storage::disk('local')->exists('a.txt'))->toBeFalse();
        expect(Storage::disk('local')->exists('b.txt'))->toBeFalse();
        Event::assertDispatched(
            BulkRenamed::class,
            fn (BulkRenamed $event): bool => count($event->renamed) === 2
        );
    });
})->covers(BulkRenameAction::class);
