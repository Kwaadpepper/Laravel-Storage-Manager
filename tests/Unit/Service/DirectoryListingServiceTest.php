<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Enum\FileSortField;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Pagination\PageNumber;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Pagination\PageSize;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Pagination\Pagination;
use Kwaadpepper\LaravelStorageManager\Service\DirectoryListingService;
use SortDirection;

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

    it('paginates clean directory contents and calculates pagination metadata', function (): void {
        // GIVEN
        Storage::disk('local')->makeDirectory('sub');
        Storage::disk('local')->put('a.txt', 'a');
        Storage::disk('local')->put('b.txt', 'bb');
        Storage::disk('local')->put('c.txt', 'ccc');
        $disk    = new Disk('local', 'local');
        $service = resolve(DirectoryListingService::class);

        // WHEN
        $page1 = $service->paginateCleanContents(
            $disk,
            '/',
            Pagination::ofPage(new PageNumber(1), new PageSize(2))
        );
        $page2 = $service->paginateCleanContents(
            $disk,
            '/',
            Pagination::ofPage(new PageNumber(2), new PageSize(2))
        );

        // THEN
        expect($page1->directories)->toHaveCount(1);
        expect($page1->directories[0]->path->value)->toBe('/sub');
        expect($page1->files)->toHaveCount(2);
        expect($page1->files[0]->path->value)->toBe('/a.txt');
        expect($page1->files[1]->path->value)->toBe('/b.txt');
        expect($page1->pagination->currentPage)->toBe(1);
        expect($page1->pagination->perPage)->toBe(2);
        expect($page1->pagination->totalFiles)->toBe(3);
        expect($page1->pagination->totalPages)->toBe(2);
        expect($page1->pagination->hasMore)->toBeTrue();

        expect($page2->directories)->toBeEmpty();
        expect($page2->files)->toHaveCount(1);
        expect($page2->files[0]->path->value)->toBe('/c.txt');
        expect($page2->pagination->currentPage)->toBe(2);
        expect($page2->pagination->hasMore)->toBeFalse();
    });

    it('sorts files by size descending when requested', function (): void {
        // GIVEN
        Storage::disk('local')->put('small.txt', '1');
        Storage::disk('local')->put('big.txt', '123456');
        Storage::disk('local')->put('medium.txt', '123');
        $disk    = new Disk('local', 'local');
        $service = resolve(DirectoryListingService::class);

        // WHEN
        $result = $service->paginateCleanContents(
            $disk,
            '/',
            Pagination::ofPage(new PageNumber(1), new PageSize(10)),
            FileSortField::Size,
            SortDirection::Descending
        );

        // THEN
        expect($result->files)->toHaveCount(3);
        expect($result->files[0]->path->value)->toBe('/big.txt');
        expect($result->files[1]->path->value)->toBe('/medium.txt');
        expect($result->files[2]->path->value)->toBe('/small.txt');
    });

    it('returns empty PaginatedDirectoryContent when path is inside trash', function (): void {
        // GIVEN
        $disk    = new Disk('local', 'local');
        $service = resolve(DirectoryListingService::class);

        // WHEN
        $result = $service->paginateCleanContents(
            $disk,
            '/.trash',
            Pagination::ofPage(new PageNumber(1), new PageSize(50))
        );

        // THEN
        expect($result->directories)->toBeEmpty();
        expect($result->files)->toBeEmpty();
        expect($result->pagination->totalFiles)->toBe(0);
        expect($result->pagination->hasMore)->toBeFalse();
    });
})->covers(DirectoryListingService::class);
