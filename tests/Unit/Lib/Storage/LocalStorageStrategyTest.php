<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Lib\Storage\LocalStorageStrategy;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;

describe('LocalStorageStrategy', function (): void {
    beforeEach(function (): void {
        Storage::fake('local');
    });

    it('reports directory metadata support as true', function (): void {
        // GIVEN
        $strategy = resolve(LocalStorageStrategy::class);
        $disk     = new Disk('local', 'local');

        // WHEN
        $supported = $strategy->supportsDirectoryMetadata($disk);

        // THEN
        expect($supported)->toBeTrue();
    });

    it('calculates used bytes correctly using flysystem listing', function (): void {
        // GIVEN
        $diskName = 'local';
        Storage::disk($diskName)->put('file1.txt', '12345');
        Storage::disk($diskName)->put('nested/file2.txt', '67890');
        $strategy = resolve(LocalStorageStrategy::class);
        $disk     = new Disk('local', $diskName);

        // WHEN
        $usedBytes = $strategy->calculateDiskUsedBytes($disk);

        // THEN
        expect($usedBytes)->toBe(10);
    });

    it('retrieves disk stats with valid mode and values', function (): void {
        // GIVEN
        $diskName = 'local';
        Storage::disk($diskName)->put('test.txt', 'hello');
        $strategy = resolve(LocalStorageStrategy::class);
        $disk     = new Disk('local', $diskName);

        // WHEN
        $stats = $strategy->getDiskStats($disk);

        // THEN
        expect($stats->mode)->toBeIn(['partition', 'folder']);
        if ($stats->mode === 'partition') {
            expect($stats->total)->toBeGreaterThan(0);
            expect($stats->free)->toBeGreaterThanOrEqual(0);
        } else {
            expect($stats->used)->toBe(5);
        }
    });
})->covers(LocalStorageStrategy::class);
