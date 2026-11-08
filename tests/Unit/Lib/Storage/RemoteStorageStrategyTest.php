<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Exception\OperationNotSupportedException;
use Kwaadpepper\LaravelStorageManager\Lib\Storage\RemoteStorageStrategy;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;

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

    it('reports public url support based on disk configuration', function (): void {
        // GIVEN
        Config::set('filesystems.disks.ftp.url', null);
        $strategy = resolve(RemoteStorageStrategy::class);
        $disk     = new Disk('ftp', 'ftp');

        // WHEN
        $unsupported = $strategy->supportsPublicUrl($disk);
        Config::set('filesystems.disks.ftp.url', 'https://ftp.example.com');
        $supported = $strategy->supportsPublicUrl($disk);

        // THEN
        expect($unsupported)->toBeFalse();
        expect($supported)->toBeTrue();
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

    it('reads file content or returns FILE_NOT_FOUND exception', function (): void {
        // GIVEN
        Storage::disk('ftp')->put('remote.txt', 'remote data');
        $strategy = resolve(RemoteStorageStrategy::class);
        $disk     = new Disk('ftp', 'ftp');

        // WHEN
        $foundResult   = $strategy->get($disk, 'remote.txt');
        $missingResult = $strategy->get($disk, 'missing.txt');

        // THEN
        expect($foundResult)->toBe('remote data');
        expect($missingResult)->toBeInstanceOf(FileOperationException::class);
        expect($missingResult->getDomainErrorCode())->toBe(FileOperationError::FILE_NOT_FOUND);
    });

    it('returns OperationNotSupportedException when public url is not configured', function (): void {
        // GIVEN
        Config::set('filesystems.disks.ftp.url', null);
        $strategy = resolve(RemoteStorageStrategy::class);
        $disk     = new Disk('ftp', 'ftp');
        $path     = new Path('/file.txt');

        // WHEN
        $result = $strategy->publicUrl($disk, $path);

        // THEN
        expect($result)->toBeInstanceOf(OperationNotSupportedException::class);
        expect($result->getDomainErrorCode())->toBe(FileOperationError::OPERATION_NOT_SUPPORTED);
    });

    it('returns OperationNotSupportedException for lastModified, visibility, and mimeType on remote directory', function (): void {
        // GIVEN
        Storage::disk('ftp')->makeDirectory('remote_dir');
        $strategy = resolve(RemoteStorageStrategy::class);
        $disk     = new Disk('ftp', 'ftp');
        $dirPath  = new Path('/remote_dir');

        // WHEN
        $lastModifiedResult = $strategy->lastModified($disk, $dirPath);
        $visibilityResult   = $strategy->visibility($disk, $dirPath);
        $mimeResult         = $strategy->mimeType($disk, $dirPath);

        // THEN
        expect($lastModifiedResult)->toBeInstanceOf(OperationNotSupportedException::class);
        expect($visibilityResult)->toBeInstanceOf(OperationNotSupportedException::class);
        expect($mimeResult)->toBeInstanceOf(OperationNotSupportedException::class);
    });

    it('moves and copies remote files and directories correctly', function (): void {
        // GIVEN
        Storage::disk('ftp')->put('f_orig.txt', 'ftp content');
        Storage::disk('ftp')->makeDirectory('ftp_dir');
        Storage::disk('ftp')->put('ftp_dir/f.txt', 'inner');
        $strategy = resolve(RemoteStorageStrategy::class);
        $disk     = new Disk('ftp', 'ftp');

        // WHEN
        $copyFileResult = $strategy->copyFile($disk, 'f_orig.txt', 'f_copy.txt');
        $moveFileResult = $strategy->moveFile($disk, 'f_copy.txt', 'f_moved.txt');
        $copyDirResult  = $strategy->copyDirectory($disk, 'ftp_dir', 'ftp_dir_copy');
        $moveDirResult  = $strategy->moveDirectory($disk, 'ftp_dir_copy', 'ftp_dir_moved');

        // THEN
        expect($copyFileResult)->toBeTrue();
        expect($moveFileResult)->toBeTrue();
        expect($copyDirResult)->toBeTrue();
        expect($moveDirResult)->toBeTrue();
        expect(Storage::disk('ftp')->exists('f_moved.txt'))->toBeTrue();
        expect(Storage::disk('ftp')->exists('ftp_dir_moved/f.txt'))->toBeTrue();
    });

    it('lists directory contents with attributes and reports optimistic subdirectories', function (): void {
        // GIVEN
        Storage::disk('ftp')->put('remote_folder/file.txt', 'remote content');
        $strategy = resolve(RemoteStorageStrategy::class);
        $disk     = new Disk('ftp', 'ftp');

        // WHEN
        $listing           = $strategy->listContents($disk, 'remote_folder');
        $hasSubdirectories = $strategy->hasSubdirectories($disk, 'remote_folder');

        // THEN
        expect($listing)->toBeArray();
        expect($listing)->not->toBeEmpty();
        expect($hasSubdirectories)->toBeTrue();
    });
})->covers(RemoteStorageStrategy::class);
