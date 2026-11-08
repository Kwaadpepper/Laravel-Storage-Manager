<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Exception\OperationNotSupportedException;
use Kwaadpepper\LaravelStorageManager\Lib\Storage\S3StorageStrategy;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;

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

    it('reports public url support as true', function (): void {
        // GIVEN
        $strategy = resolve(S3StorageStrategy::class);
        $disk     = new Disk('s3', 's3');

        // WHEN
        $supported = $strategy->supportsPublicUrl($disk);

        // THEN
        expect($supported)->toBeTrue();
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

    it('reads file content or returns FILE_NOT_FOUND exception', function (): void {
        // GIVEN
        Storage::disk('s3')->put('cloud.txt', 'cloud data');
        $strategy = resolve(S3StorageStrategy::class);
        $disk     = new Disk('s3', 's3');

        // WHEN
        $foundResult   = $strategy->get($disk, 'cloud.txt');
        $missingResult = $strategy->get($disk, 'missing.txt');

        // THEN
        expect($foundResult)->toBe('cloud data');
        expect($missingResult)->toBeInstanceOf(FileOperationException::class);
        expect($missingResult->getDomainErrorCode())->toBe(FileOperationError::FILE_NOT_FOUND);
    });

    it('returns OperationNotSupportedException for lastModified, visibility, and mimeType on virtual directory', function (): void {
        // GIVEN
        Storage::disk('s3')->makeDirectory('virtual_dir');
        $strategy = resolve(S3StorageStrategy::class);
        $disk     = new Disk('s3', 's3');
        $dirPath  = new Path('/virtual_dir');

        // WHEN
        $lastModifiedResult = $strategy->lastModified($disk, $dirPath);
        $visibilityResult   = $strategy->visibility($disk, $dirPath);
        $mimeResult         = $strategy->mimeType($disk, $dirPath);

        // THEN
        expect($lastModifiedResult)->toBeInstanceOf(OperationNotSupportedException::class);
        expect($visibilityResult)->toBeInstanceOf(OperationNotSupportedException::class);
        expect($mimeResult)->toBeInstanceOf(OperationNotSupportedException::class);
    });

    it('moves and copies objects on S3 correctly', function (): void {
        // GIVEN
        Storage::disk('s3')->put('s3_orig.txt', 's3 content');
        Storage::disk('s3')->makeDirectory('s3_dir');
        Storage::disk('s3')->put('s3_dir/f.txt', 'inner');
        $strategy = resolve(S3StorageStrategy::class);
        $disk     = new Disk('s3', 's3');

        // WHEN
        $copyFileResult = $strategy->copyFile($disk, 's3_orig.txt', 's3_copy.txt');
        $moveFileResult = $strategy->moveFile($disk, 's3_copy.txt', 's3_moved.txt');
        $copyDirResult  = $strategy->copyDirectory($disk, 's3_dir', 's3_dir_copy');
        $moveDirResult  = $strategy->moveDirectory($disk, 's3_dir_copy', 's3_dir_moved');

        // THEN
        expect($copyFileResult)->toBeTrue();
        expect($moveFileResult)->toBeTrue();
        expect($copyDirResult)->toBeTrue();
        expect($moveDirResult)->toBeTrue();
        expect(Storage::disk('s3')->exists('s3_moved.txt'))->toBeTrue();
        expect(Storage::disk('s3')->exists('s3_dir_moved/f.txt'))->toBeTrue();
    });
})->covers(S3StorageStrategy::class);
