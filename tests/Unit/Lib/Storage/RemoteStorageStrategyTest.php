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

    it('performs basic file and directory management operations on remote', function (): void {
        // GIVEN
        $strategy = resolve(RemoteStorageStrategy::class);
        $disk     = new Disk('ftp', 'ftp');

        // WHEN
        $putResult        = $strategy->put($disk, 'remote_put.txt', 'hello remote put');
        $makeDirResult    = $strategy->makeDirectory($disk, 'remote_test_dir');
        $deleteFileResult = $strategy->delete($disk, 'remote_put.txt');
        $deleteDirResult  = $strategy->deleteDirectory($disk, 'remote_test_dir');

        // THEN
        expect($putResult)->toBeTrue();
        expect($makeDirResult)->toBeTrue();
        expect($deleteFileResult)->toBeTrue();
        expect($deleteDirResult)->toBeTrue();
    });

    it('checks exists isDirectory and isFile accurately on remote', function (): void {
        // GIVEN
        Storage::disk('ftp')->put('remote_file.txt', 'sample remote');
        Storage::disk('ftp')->makeDirectory('remote_dir_check');
        $strategy = resolve(RemoteStorageStrategy::class);
        $disk     = new Disk('ftp', 'ftp');
        $rootPath = new Path('/');
        $filePath = new Path('/remote_file.txt');
        $dirPath  = new Path('/remote_dir_check');
        $missing  = new Path('/missing_remote.txt');

        // WHEN
        $rootExists    = $strategy->exists($disk, $rootPath);
        $fileExists    = $strategy->exists($disk, $filePath);
        $dirExists     = $strategy->exists($disk, $dirPath);
        $missingExists = $strategy->exists($disk, $missing);

        $rootIsDir  = $strategy->isDirectory($disk, $rootPath);
        $dirIsDir   = $strategy->isDirectory($disk, $dirPath);
        $fileIsDir  = $strategy->isDirectory($disk, $filePath);

        $rootIsFile = $strategy->isFile($disk, $rootPath);
        $fileIsFile = $strategy->isFile($disk, $filePath);
        $dirIsFile  = $strategy->isFile($disk, $dirPath);

        // THEN
        expect($rootExists)->toBeTrue();
        expect($fileExists)->toBeTrue();
        expect($dirExists)->toBeTrue();
        expect($missingExists)->toBeFalse();

        expect($rootIsDir)->toBeTrue();
        expect($dirIsDir)->toBeTrue();
        expect($fileIsDir)->toBeFalse();

        expect($rootIsFile)->toBeFalse();
        expect($fileIsFile)->toBeTrue();
        expect($dirIsFile)->toBeFalse();
    });

    it('retrieves file size and directory listings on remote', function (): void {
        // GIVEN
        Storage::disk('ftp')->put('remote_listing/f1.txt', '1234');
        Storage::disk('ftp')->put('remote_listing/sub/f2.txt', '5678');
        $strategy = resolve(RemoteStorageStrategy::class);
        $disk     = new Disk('ftp', 'ftp');

        // WHEN
        $size           = $strategy->size($disk, 'remote_listing/f1.txt');
        $files          = $strategy->files($disk, 'remote_listing');
        $dirs           = $strategy->directories($disk, 'remote_listing');
        $allFiles       = $strategy->allFiles($disk, 'remote_listing');
        $allDirectories = $strategy->allDirectories($disk, 'remote_listing');

        // THEN
        expect($size)->toBe(4);
        expect($files)->toBeArray();
        expect($dirs)->toBeArray();
        expect($allFiles)->toBeArray();
        expect($allDirectories)->toBeArray();
    });

    it('reads and writes streams correctly on remote', function (): void {
        // GIVEN
        Storage::disk('ftp')->put('remote_stream_src.txt', 'remote stream content');
        $strategy = resolve(RemoteStorageStrategy::class);
        $disk     = new Disk('ftp', 'ftp');
        $srcPath  = new Path('/remote_stream_src.txt');
        $destPath = new Path('/remote_stream_dest.txt');

        // WHEN
        $stream      = $strategy->readStream($disk, $srcPath);
        $isResource  = is_resource($stream);
        $writeResult = $isResource ? $strategy->writeStream($disk, $destPath, $stream) : false;
        if ($isResource) {
            fclose($stream);
        }

        // THEN
        expect($isResource)->toBeTrue();
        expect($writeResult)->toBeTrue();
        expect($strategy->get($disk, 'remote_stream_dest.txt'))->toBe('remote stream content');
    });

    it('retrieves lastModified visibility mimeType and publicUrl on remote file', function (): void {
        // GIVEN
        Config::set('filesystems.disks.ftp.url', 'https://ftp.example.com');
        Storage::disk('ftp')->put('remote_meta.txt', 'meta content');
        $strategy = resolve(RemoteStorageStrategy::class);
        $disk     = new Disk('ftp', 'ftp');
        $filePath = new Path('/remote_meta.txt');

        // WHEN
        $lastModified = $strategy->lastModified($disk, $filePath);
        $visibility   = $strategy->visibility($disk, $filePath);
        $mime         = $strategy->mimeType($disk, $filePath);
        $url          = $strategy->publicUrl($disk, $filePath);

        // THEN
        expect($lastModified)->toBeInt();
        expect($visibility)->toBeString();
        expect($mime)->toBeString();
        expect($url)->toBeString();
    });
})->covers(RemoteStorageStrategy::class);
