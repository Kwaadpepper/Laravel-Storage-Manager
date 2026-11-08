<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\FilePropertyExtractor;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathVisibility;

describe('FilePropertyExtractorTest', function (): void {
    it('extracts file properties correctly', function (): void {
        // GIVEN
        $diskName  = 'test_disk';
        $path      = new Path('/path/to/file.txt');
        Storage::fake($diskName);
        Storage::disk($diskName)->put($path, 'some content');
        $disk      = new Disk(driver: 'local', name: $diskName, throw: false, report: false);
        $extractor = resolve(FilePropertyExtractor::class);

        // WHEN
        $properties = $extractor->fileProperties($disk, $path);

        // THEN
        expect($properties->path)->toBe($path);
        expect($properties->basename)->toBe('file.txt');
        expect($properties->dirname)->toBe('/path/to');
        expect($properties->extension)->toBe('txt');
        expect($properties->filename)->toBe('file');
        expect($properties->size)->toBe(12);
        expect($properties->timestamp)->toBeBetween(
            new \DateTimeImmutable('-1 minute'),
            new \DateTimeImmutable('+1 minute')
        );
        expect($properties->visibility)->toBe(PathVisibility::PUBLIC);
        expect($properties->isFile)->toBe(true);
        expect($properties->isDirectory)->toBe(false);
    });

    it('extracts directory properties with null visibility for sftp driver', function (): void {
        // GIVEN
        $diskName  = 'sftp_disk';
        $path      = new Path('/path/to/dir');
        Storage::fake($diskName);
        Storage::disk($diskName)->makeDirectory('/path/to/dir');
        $disk      = new Disk(driver: 'sftp', name: $diskName, throw: false, report: false);
        $extractor = resolve(FilePropertyExtractor::class);

        // WHEN
        $properties = $extractor->directoryProperties($disk, $path);

        // THEN
        expect($properties->path)->toBe($path);
        expect($properties->basename)->toBe('dir');
        expect($properties->dirname)->toBe('/path/to');
        expect($properties->visibility)->toBeNull();
        expect($properties->isDirectory)->toBe(true);
        expect($properties->isFile)->toBe(false);
    });

    it('extracts directory properties with visibility and timestamp for local driver', function (): void {
        // GIVEN
        $diskName  = 'local_disk';
        $path      = new Path('/path/to/localdir');
        Storage::fake($diskName);
        Storage::disk($diskName)->makeDirectory('/path/to/localdir');
        $disk      = new Disk(driver: 'local', name: $diskName, throw: false, report: false);
        $extractor = resolve(FilePropertyExtractor::class);

        // WHEN
        $properties = $extractor->directoryProperties($disk, $path);

        // THEN
        expect($properties->path)->toBe($path);
        expect($properties->basename)->toBe('localdir');
        expect($properties->dirname)->toBe('/path/to');
        expect($properties->visibility)->not->toBeNull();
        expect($properties->isDirectory)->toBe(true);
        expect($properties->isFile)->toBe(false);
    });
})->covers(FilePropertyExtractor::class);
