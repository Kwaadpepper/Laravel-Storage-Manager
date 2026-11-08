<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Pagination\PageNumber;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Pagination\PageSize;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Pagination\Pagination;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Query\Tree\GetDirectoryContentInput;
use Kwaadpepper\LaravelStorageManager\Query\Tree\GetDirectoryContentQuery;

describe('GetDirectoryContentQuery', function (): void {
    beforeEach(function (): void {
        Storage::fake('local');
        Config::set('filesystems.disks.local', [
            'driver' => 'local',
        ]);
        Config::set('storage-manager.disks.available', ['local']);
    });

    it('returns empty content when path is trash folder', function (): void {
        // GIVEN
        $disk   = new Disk('local', 'local');
        $query  = resolve(GetDirectoryContentQuery::class);
        $input  = new GetDirectoryContentInput($disk, new Path('/.trash'));

        // WHEN
        $result = $query($input);

        // THEN
        expect($result->directories)->toBeEmpty();
        expect($result->files)->toBeEmpty();
        expect($result->pagination->totalFiles)->toBe(0);
        expect($result->pagination->hasMore)->toBeFalse();
    });

    it('returns list of files and directories with metadata and pagination', function (): void {
        // GIVEN
        Storage::disk('local')->makeDirectory('folder');
        Storage::disk('local')->put('document.pdf', 'content');
        $disk   = new Disk('local', 'local');
        $query  = resolve(GetDirectoryContentQuery::class);
        $input  = new GetDirectoryContentInput($disk, new Path('/'));

        // WHEN
        $result = $query($input);

        // THEN
        expect($result->directories)->toHaveCount(1);
        expect($result->files)->toHaveCount(1);
        expect($result->directories[0]->path->value)->toBe('/folder');
        expect($result->files[0]->path->value)->toBe('/document.pdf');
        expect($result->files[0]->size)->toBe(7);
        expect($result->files[0]->extension)->toBe('pdf');
        expect($result->pagination->totalFiles)->toBe(1);
        expect($result->pagination->currentPage)->toBe(1);
        expect($result->pagination->hasMore)->toBeFalse();
    });

    it('applies pagination parameters and returns sliced files', function (): void {
        // GIVEN
        Storage::disk('local')->put('item1.txt', '1');
        Storage::disk('local')->put('item2.txt', '2');
        Storage::disk('local')->put('item3.txt', '3');
        $disk   = new Disk('local', 'local');
        $query  = resolve(GetDirectoryContentQuery::class);
        $input  = new GetDirectoryContentInput(
            $disk,
            new Path('/'),
            Pagination::ofPage(new PageNumber(1), new PageSize(2)),
        );

        // WHEN
        $result = $query($input);

        // THEN
        expect($result->files)->toHaveCount(2);
        expect($result->pagination->currentPage)->toBe(1);
        expect($result->pagination->perPage)->toBe(2);
        expect($result->pagination->totalFiles)->toBe(3);
        expect($result->pagination->totalPages)->toBe(2);
        expect($result->pagination->hasMore)->toBeTrue();
    });
})->covers(GetDirectoryContentQuery::class);
