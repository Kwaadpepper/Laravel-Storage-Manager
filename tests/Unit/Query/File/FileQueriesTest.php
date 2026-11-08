<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Query\File\CheckPathExistsInput;
use Kwaadpepper\LaravelStorageManager\Query\File\CheckPathExistsQuery;
use Kwaadpepper\LaravelStorageManager\Query\File\GetPathPropertiesInput;
use Kwaadpepper\LaravelStorageManager\Query\File\GetPathPropertiesQuery;

describe('FileQueries', function (): void {
    beforeEach(function (): void {
        Storage::fake('local');
        Config::set('filesystems.disks.local', [
            'driver' => 'local',
        ]);
        Config::set('storage-manager.disks.available', ['local']);
    });

    it('checks if a path exists via CheckPathExistsQuery', function (): void {
        // GIVEN
        Storage::disk('local')->put('exists.txt', 'yes');
        $disk  = new Disk('local', 'local');
        $path  = new Path('/exists.txt');
        $query = resolve(CheckPathExistsQuery::class);
        $input = new CheckPathExistsInput($disk, $path);

        // WHEN
        $existsTrue  = $query($input);
        $existsFalse = $query(new CheckPathExistsInput($disk, new Path('/not_exists.txt')));

        // THEN
        expect($existsTrue)->toBeTrue();
        expect($existsFalse)->toBeFalse();
        expect($input->getDisk())->toBe($disk);
        expect($input->getPath())->toBe($path);
    });

    it('retrieves path properties for a file via GetPathPropertiesQuery', function (): void {
        // GIVEN
        Storage::disk('local')->put('info.txt', 'sample');
        $disk  = new Disk('local', 'local');
        $path  = new Path('/info.txt');
        $query = resolve(GetPathPropertiesQuery::class);
        $input = new GetPathPropertiesInput($disk, $path);

        // WHEN
        $properties = $query($input);

        // THEN
        expect($properties->basename)->toBe('info.txt');
        expect($properties->size)->toBe(6);
        expect($input->getDisk())->toBe($disk);
        expect($input->getPath())->toBe($path);
    });

    it('retrieves path properties for a directory via GetPathPropertiesQuery', function (): void {
        // GIVEN
        Storage::disk('local')->makeDirectory('sub_dir');
        $disk  = new Disk('local', 'local');
        $query = resolve(GetPathPropertiesQuery::class);
        $input = new GetPathPropertiesInput($disk, new Path('/sub_dir'));

        // WHEN
        $properties = $query($input);

        // THEN
        expect($properties->basename)->toBe('sub_dir');
        expect($properties->isDirectory)->toBeTrue();
    });
})->covers(CheckPathExistsQuery::class, GetPathPropertiesQuery::class, CheckPathExistsInput::class, GetPathPropertiesInput::class);
