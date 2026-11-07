<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
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
    });

    it('returns list of files and directories', function (): void {
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
        expect($result->directories[0]->value)->toBe('/folder');
        expect($result->files[0]->value)->toBe('/document.pdf');
    });
})->covers(GetDirectoryContentQuery::class);
