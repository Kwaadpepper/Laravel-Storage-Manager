<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Lib\Storage\S3StorageStrategy;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;

describe('S3StorageStrategy', function (): void {
    beforeEach(function (): void {
        Storage::fake('s3');
    });

    it('reports directory metadata support as false', function (): void {
        // GIVEN
        $strategy = resolve(S3StorageStrategy::class);
        $disk     = new Disk('s3', 's3');

        // WHEN
        $supported = $strategy->supportsDirectoryMetadata($disk);

        // THEN
        expect($supported)->toBeFalse();
    });

    it('calculates used bytes correctly', function (): void {
        // GIVEN
        Storage::disk('s3')->put('sample.txt', 'cloud content');
        $strategy = resolve(S3StorageStrategy::class);
        $disk     = new Disk('s3', 's3');

        // WHEN
        $usedBytes = $strategy->calculateDiskUsedBytes($disk);

        // THEN
        expect($usedBytes)->toBe(13);
    });

    it('returns unlimited disk stats mode', function (): void {
        // GIVEN
        $strategy = resolve(S3StorageStrategy::class);
        $disk     = new Disk('s3', 's3');

        // WHEN
        $stats = $strategy->getDiskStats($disk);

        // THEN
        expect($stats->mode)->toBe('unlimited');
        expect($stats->total)->toBeNull();
        expect($stats->free)->toBeNull();
        expect($stats->used)->toBeNull();
    });
})->covers(S3StorageStrategy::class);
