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

    it('lists directory contents with attributes in batch and reports optimistic subdirectories', function (): void {
        // GIVEN
        Storage::disk('s3')->put('folder/sample.txt', 'sample s3 content');
        Storage::disk('s3')->makeDirectory('folder/sub');
        $strategy = resolve(S3StorageStrategy::class);
        $disk     = new Disk('s3', 's3');

        // WHEN
        $listing           = $strategy->listContents($disk, 'folder');
        $hasSubdirectories = $strategy->hasSubdirectories($disk, 'folder/sub');

        // THEN
        expect($listing)->toBeArray();
        expect($listing)->not->toBeEmpty();
        expect($hasSubdirectories)->toBeTrue();
    });

    it('performs basic file and directory management operations on s3', function (): void {
        // GIVEN
        $strategy = resolve(S3StorageStrategy::class);
        $disk     = new Disk('s3', 's3');

        // WHEN
        $putResult        = $strategy->put($disk, 's3_put.txt', 'hello s3 put');
        $makeDirResult    = $strategy->makeDirectory($disk, 's3_test_dir');
        $deleteFileResult = $strategy->delete($disk, 's3_put.txt');
        $deleteDirResult  = $strategy->deleteDirectory($disk, 's3_test_dir');

        // THEN
        expect($putResult)->toBeTrue();
        expect($makeDirResult)->toBeTrue();
        expect($deleteFileResult)->toBeTrue();
        expect($deleteDirResult)->toBeTrue();
    });

    it('checks exists isDirectory and isFile accurately on s3', function (): void {
        // GIVEN
        Storage::disk('s3')->put('s3_file.txt', 'sample s3');
        Storage::disk('s3')->makeDirectory('s3_dir_check');
        $strategy = resolve(S3StorageStrategy::class);
        $disk     = new Disk('s3', 's3');
        $rootPath = new Path('/');
        $filePath = new Path('/s3_file.txt');
        $dirPath  = new Path('/s3_dir_check');
        $missing  = new Path('/missing_s3.txt');

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

    it('retrieves file size and directory listings on s3', function (): void {
        // GIVEN
        Storage::disk('s3')->put('s3_listing/f1.txt', '1234');
        Storage::disk('s3')->put('s3_listing/sub/f2.txt', '5678');
        $strategy = resolve(S3StorageStrategy::class);
        $disk     = new Disk('s3', 's3');

        // WHEN
        $size           = $strategy->size($disk, 's3_listing/f1.txt');
        $files          = $strategy->files($disk, 's3_listing');
        $dirs           = $strategy->directories($disk, 's3_listing');
        $allFiles       = $strategy->allFiles($disk, 's3_listing');
        $allDirectories = $strategy->allDirectories($disk, 's3_listing');

        // THEN
        expect($size)->toBe(4);
        expect($files)->toBeArray();
        expect($dirs)->toBeArray();
        expect($allFiles)->toBeArray();
        expect($allDirectories)->toBeArray();
    });

    it('reads and writes streams correctly on s3', function (): void {
        // GIVEN
        Storage::disk('s3')->put('s3_stream_src.txt', 's3 stream content');
        $strategy = resolve(S3StorageStrategy::class);
        $disk     = new Disk('s3', 's3');
        $srcPath  = new Path('/s3_stream_src.txt');
        $destPath = new Path('/s3_stream_dest.txt');

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
        expect($strategy->get($disk, 's3_stream_dest.txt'))->toBe('s3 stream content');
    });

    it('retrieves lastModified visibility mimeType and publicUrl on s3 file', function (): void {
        // GIVEN
        Storage::disk('s3')->put('s3_meta.txt', 'meta content');
        $strategy = resolve(S3StorageStrategy::class);
        $disk     = new Disk('s3', 's3');
        $filePath = new Path('/s3_meta.txt');

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
})->covers(S3StorageStrategy::class);
