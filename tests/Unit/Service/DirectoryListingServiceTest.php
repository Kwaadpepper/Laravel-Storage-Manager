<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Service\DirectoryListingService;

describe('DirectoryListingService', function (): void {
    beforeEach(function (): void {
        Storage::fake('local');
        Config::set('filesystems.disks.local', [
            'driver' => 'local',
        ]);
        Config::set('storage-manager.disks.available', ['local']);
        Config::set('storage-manager.trash.enabled', true);
        Config::set('storage-manager.trash.folder', '.trash');
    });

    it('identifies whether a path is inside trash or not', function (): void {
        // GIVEN
        $service = resolve(DirectoryListingService::class);

        // WHEN
        $isTrashFolder  = $service->isTrashPath('/.trash');
        $isInsideTrash  = $service->isTrashPath('/.trash/sub/file.txt');
        $isNormalFolder = $service->isTrashPath('/documents');
        $isNormalFile   = $service->isTrashPath('/documents/report.pdf');
        $isSimilarName  = $service->isTrashPath('/.trash_backup');

        // THEN
        expect($isTrashFolder)->toBeTrue();
        expect($isInsideTrash)->toBeTrue();
        expect($isNormalFolder)->toBeFalse();
        expect($isNormalFile)->toBeFalse();
        expect($isSimilarName)->toBeFalse();
    });

    it('correctly checks whether a directory has subdirectories', function (): void {
        // GIVEN
        Storage::disk('local')->makeDirectory('parent/child');
        $disk    = new Disk('local', 'local');
        $service = resolve(DirectoryListingService::class);

        // WHEN
        $hasChildSubdirs = $service->hasSubdirectories($disk, '/parent');

        // THEN
        expect($hasChildSubdirs)->toBeTrue();
    });

    it('returns false for hasSubdirectories when directory only contains trash folder', function (): void {
        // GIVEN
        Storage::disk('local')->makeDirectory('.trash');
        $disk    = new Disk('local', 'local');
        $service = resolve(DirectoryListingService::class);

        // WHEN
        $hasTrashOnly = $service->hasSubdirectories($disk, '/');

        // THEN
        expect($hasTrashOnly)->toBeFalse();
    });

    it('lists clean directory entries excluding root trash folder and trash contents', function (): void {
        // GIVEN
        Storage::disk('local')->makeDirectory('docs');
        Storage::disk('local')->makeDirectory('.trash');
        Storage::disk('local')->put('root_file.txt', 'hello');
        Storage::disk('local')->put('.trash/trashed.txt', 'bye');
        $disk    = new Disk('local', 'local');
        $service = resolve(DirectoryListingService::class);

        // WHEN
        $entries = $service->listCleanEntries($disk, '/');

        // THEN
        expect($entries['directories'])->toContain('/docs');
        expect($entries['directories'])->not->toContain('/.trash');
        expect($entries['files'])->toContain('/root_file.txt');
        expect($entries['files'])->not->toContain('/.trash/trashed.txt');
    });
})->covers(DirectoryListingService::class);
