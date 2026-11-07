<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Action\Archive\UnzipAction;
use Kwaadpepper\LaravelStorageManager\Action\Archive\UnzipInput;
use Kwaadpepper\LaravelStorageManager\Event\ArchiveExtracted;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;

describe('UnzipAction', function (): void {
    beforeEach(function (): void {
        Storage::fake('local');
        Config::set('filesystems.disks.local', [
            'driver' => 'local',
        ]);
        Config::set('storage-manager.disks.available', ['local']);
        Config::set('storage-manager.upload.allowed_extensions', 'txt,zip,pdf');
    });

    it('extracts zip archive and dispatches ArchiveExtracted', function (): void {
        // GIVEN
        Event::fake([ArchiveExtracted::class]);
        $tempZip = tempnam(sys_get_temp_dir(), 'test_zip_');
        assert(is_string($tempZip));
        try {
            $zip = new \ZipArchive();
            $zip->open($tempZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
            $zip->addFromString('extracted.txt', 'Extracted content');
            $zip->close();
            Storage::disk('local')->put('test.zip', (string) file_get_contents($tempZip));
        } finally {
            if (file_exists($tempZip)) {
                @unlink($tempZip);
            }
        }
        $disk   = new Disk('local', 'local');
        $action = resolve(UnzipAction::class);
        $input  = new UnzipInput($disk, new Path('/test.zip'), new Path('/output'));

        // WHEN
        $result = $action($input);

        // THEN
        expect($result->value)->toBe('/output');
        expect(Storage::disk('local')->exists('output/extracted.txt'))->toBeTrue();
        Event::assertDispatched(
            ArchiveExtracted::class,
            fn (ArchiveExtracted $event): bool => $event->path === '/test.zip' && $event->destination === '/output'
        );
    });
})->covers(UnzipAction::class);
