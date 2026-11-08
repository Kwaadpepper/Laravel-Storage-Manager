<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;
use Kwaadpepper\LaravelStorageManager\Event\ArchiveExtracted;
use Kwaadpepper\LaravelStorageManager\Event\ArchiveExtracting;
use Kwaadpepper\LaravelStorageManager\Event\ZipCreated;
use Kwaadpepper\LaravelStorageManager\Event\ZipCreating;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Exception\OperationCancelledException;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Service\ArchiveService;

describe('ArchiveService', function (): void {
    beforeEach(function (): void {
        Storage::fake('local');
        Config::set('filesystems.disks.local', [
            'driver' => 'local',
        ]);
        Config::set('storage-manager.disks.available', ['local']);
        Config::set('storage-manager.upload.allowed_extensions', 'txt,zip,pdf');
    });

    it('reports zip extension support correctly', function (): void {
        // GIVEN
        $service = resolve(ArchiveService::class);

        // WHEN
        $supported = $service->supportsZip();

        // THEN
        expect($supported)->toBe(extension_loaded('zip') && class_exists(\ZipArchive::class));
    });

    it('creates zip archive from paths and dispatches ZipCreated', function (): void {
        // GIVEN
        Event::fake([ZipCreated::class]);
        Storage::disk('local')->put('file1.txt', 'Content 1');
        Storage::disk('local')->put('file2.txt', 'Content 2');
        $disk    = new Disk('local', 'local');
        $service = resolve(ArchiveService::class);

        // WHEN
        $result = $service->zip($disk, [new Path('/file1.txt'), new Path('/file2.txt')], new Path('/bundle.zip'));

        // THEN
        expect($result->value)->toBe('/bundle.zip');
        expect(Storage::disk('local')->exists('bundle.zip'))->toBeTrue();
        Event::assertDispatched(
            ZipCreated::class,
            fn (ZipCreated $event): bool => $event->path === '/bundle.zip'
        );
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
        $disk    = new Disk('local', 'local');
        $service = resolve(ArchiveService::class);

        // WHEN
        $service->unzip($disk, new Path('/test.zip'), new Path('/output'));

        // THEN
        expect(Storage::disk('local')->exists('output/extracted.txt'))->toBeTrue();
        Event::assertDispatched(
            ArchiveExtracted::class,
            fn (ArchiveExtracted $event): bool => $event->path === '/test.zip' && $event->destination === '/output'
        );
    });

    it('throws when unzipping a non-zip file', function (): void {
        // GIVEN
        Storage::disk('local')->put('document.txt', 'not a zip');
        $disk    = new Disk('local', 'local');
        $service = resolve(ArchiveService::class);

        // WHEN
        $action = function () use ($service, $disk): void {
            $service->unzip($disk, new Path('/document.txt'), new Path('/out'));
        };

        // THEN
        expect($action)->toThrow(FileOperationException::class, FileOperationError::INVALID_ARCHIVE->message());
    });

    it('throws OperationCancelledException and prevents zipping when ZipCreating is cancelled', function (): void {
        // GIVEN
        Event::listen(ZipCreating::class, function (ZipCreating $event): void {
            $event->cancel('Archiving blocked');
        });
        Storage::disk('local')->put('file1.txt', 'Content 1');
        $disk    = new Disk('local', 'local');
        $service = resolve(ArchiveService::class);

        // WHEN
        $action = fn (): Path => $service->zip($disk, [new Path('/file1.txt')], new Path('/blocked.zip'));

        // THEN
        expect($action)->toThrow(OperationCancelledException::class, 'Archiving blocked');
        expect(Storage::disk('local')->exists('blocked.zip'))->toBeFalse();
    });

    it('throws OperationCancelledException and prevents unzipping when ArchiveExtracting is cancelled', function (): void {
        // GIVEN
        $tempZip = tempnam(sys_get_temp_dir(), 'test_zip_');
        assert(is_string($tempZip));
        try {
            $zip = new \ZipArchive();
            $zip->open($tempZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
            $zip->addFromString('data.txt', 'some data');
            $zip->close();
            Storage::disk('local')->put('archive.zip', (string) file_get_contents($tempZip));
        } finally {
            if (file_exists($tempZip)) {
                @unlink($tempZip);
            }
        }

        Event::listen(ArchiveExtracting::class, function (ArchiveExtracting $event): void {
            $event->cancel('Extraction disallowed');
        });

        $disk    = new Disk('local', 'local');
        $service = resolve(ArchiveService::class);

        // WHEN
        $action = function () use ($service, $disk): void {
            $service->unzip($disk, new Path('/archive.zip'), new Path('/out_blocked'));
        };

        // THEN
        expect($action)->toThrow(OperationCancelledException::class, 'Extraction disallowed');
        expect(Storage::disk('local')->exists('out_blocked/data.txt'))->toBeFalse();
    });
})->covers(ArchiveService::class);
