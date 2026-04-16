<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\FilePropertyExtractor;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathVisibility;

describe('FilePropertyExtractorTest', function (): void {
    it('extracts file properties correctly', function (): void {
        // Given
        $diskName  = 'test_disk';
        $path      = new Path('/path/to/file.txt');
        Storage::fake($diskName);
        Storage::disk($diskName)->put($path, 'some content');
        $disk      = new Disk(driver: 'local', name: $diskName, throw: false, report: false);
        $extractor = new FilePropertyExtractor();

        // When
        $properties = $extractor->fileProperties($disk, $path);

        // Then
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
});
