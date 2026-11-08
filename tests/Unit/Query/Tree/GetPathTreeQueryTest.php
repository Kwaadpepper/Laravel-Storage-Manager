<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Query\Tree\GetPathTreeInput;
use Kwaadpepper\LaravelStorageManager\Query\Tree\GetPathTreeQuery;

describe('GetPathTreeQuery', function (): void {
    beforeEach(function (): void {
        Storage::fake('local');
        Config::set('filesystems.disks.local', [
            'driver' => 'local',
        ]);
        Config::set('storage-manager.disks.available', ['local']);
    });

    it('returns empty level if directory is trash path', function (): void {
        // GIVEN
        $disk   = new Disk('local', 'local');
        $query  = resolve(GetPathTreeQuery::class);
        $input  = new GetPathTreeInput($disk, new Path('/.trash'));

        // WHEN
        $result = $query($input);

        // THEN
        expect($result->directories)->toBeEmpty();
        expect($result->files)->toBeEmpty();
    });

    it('returns directory tree with files and subdirectories', function (): void {
        // GIVEN
        Storage::disk('local')->makeDirectory('sub');
        Storage::disk('local')->put('file.txt', 'hello');
        $disk   = new Disk('local', 'local');
        $query  = resolve(GetPathTreeQuery::class);
        $input  = new GetPathTreeInput($disk, new Path('/'));

        // WHEN
        $result = $query($input);

        // THEN
        expect($result->directories)->toHaveCount(1);
        expect($result->files)->toHaveCount(1);
        expect($result->directories[0]->path->value)->toBe('/sub');
        expect($result->files[0]->path->value)->toBe('/file.txt');
    });

    it('populates file and directory properties from batch listing without individual queries', function (): void {
        // GIVEN
        Storage::disk('local')->put('photos/beach.png', str_repeat('P', 256));
        Storage::disk('local')->makeDirectory('photos/vacation');
        $disk   = new Disk('local', 'local');
        $query  = resolve(GetPathTreeQuery::class);
        $input  = new GetPathTreeInput($disk, new Path('/photos'));

        // WHEN
        $result = $query($input);

        // THEN
        expect($result->directories)->toHaveCount(1);
        expect($result->files)->toHaveCount(1);
        expect($result->files[0]->size)->toBe(256);
        expect($result->files[0]->extension)->toBe('png');
        expect($result->directories[0]->path->value)->toBe('/photos/vacation');
    });
})->covers(GetPathTreeQuery::class);
