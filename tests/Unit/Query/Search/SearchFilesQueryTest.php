<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Acl\SmAbility;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Query\Search\SearchFilesInput;
use Kwaadpepper\LaravelStorageManager\Query\Search\SearchFilesQuery;

describe('SearchFilesQuery', function (): void {
    beforeEach(function (): void {
        Storage::fake('local');
        Config::set('filesystems.disks.local', [
            'driver' => 'local',
        ]);
        Config::set('storage-manager.disks.available', ['local']);
    });

    it('returns SEARCH ability', function (): void {
        // GIVEN
        $query = resolve(SearchFilesQuery::class);

        // WHEN
        $ability = $query->ability();

        // THEN
        expect($ability)->toBe(SmAbility::SEARCH);
    });

    it('searches files matching query', function (): void {
        // GIVEN
        Storage::disk('local')->put('docs/readme.txt', 'hello world');
        Storage::disk('local')->put('docs/notes.md', 'some notes');
        $disk   = new Disk('local', 'local');
        $paths  = [new Path('/docs')];
        $input  = new SearchFilesInput($disk, $paths, query: 'readme');
        $query  = resolve(SearchFilesQuery::class);

        // WHEN
        $result = $query($input);

        // THEN
        expect($result->matchedFiles)->toHaveCount(1);
    });
})->covers(SearchFilesQuery::class);
