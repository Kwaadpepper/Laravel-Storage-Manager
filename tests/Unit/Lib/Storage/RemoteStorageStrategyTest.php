<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Lib\Storage\RemoteStorageStrategy;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;

describe('RemoteStorageStrategy', function (): void {
    beforeEach(function (): void {
        Storage::fake('ftp');
    });

    it('reports directory metadata support as false', function (): void {
        // GIVEN
        $strategy = resolve(RemoteStorageStrategy::class);
        $disk     = new Disk('ftp', 'ftp');

        // WHEN
        $supported = $strategy->supportsDirectoryMetadata($disk);

        // THEN
        expect($supported)->toBeFalse();
    });

    it('calculates used bytes correctly', function (): void {
        // GIVEN
        Storage::disk('ftp')->put('file.txt', 'remote content');
        $strategy = resolve(RemoteStorageStrategy::class);
        $disk     = new Disk('ftp', 'ftp');

        // WHEN
        $usedBytes = $strategy->calculateDiskUsedBytes($disk);

        // THEN
        expect($usedBytes)->toBe(14);
    });

    it('returns unlimited disk stats mode', function (): void {
        // GIVEN
        $strategy = resolve(RemoteStorageStrategy::class);
        $disk     = new Disk('ftp', 'ftp');

        // WHEN
        $stats = $strategy->getDiskStats($disk);

        // THEN
        expect($stats->mode)->toBe('unlimited');
        expect($stats->total)->toBeNull();
        expect($stats->free)->toBeNull();
        expect($stats->used)->toBeNull();
    });
})->covers(RemoteStorageStrategy::class);
