<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Exception\OperationNotSupportedException;
use Kwaadpepper\LaravelStorageManager\Lib\Storage\LocalStorageStrategy;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;

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

    it('reports public url support as true', function (): void {
        // GIVEN
        $strategy = resolve(LocalStorageStrategy::class);
        $disk     = new Disk('local', 'local');

        // WHEN
        $supported = $strategy->supportsPublicUrl($disk);

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

    it('reads file content or returns FILE_NOT_FOUND exception', function (): void {
        // GIVEN
        Storage::disk('local')->put('existing.txt', 'hello content');
        $strategy = resolve(LocalStorageStrategy::class);
        $disk     = new Disk('local', 'local');

        // WHEN
        $foundResult   = $strategy->get($disk, 'existing.txt');
        $missingResult = $strategy->get($disk, 'missing.txt');

        // THEN
        expect($foundResult)->toBe('hello content');
        expect($missingResult)->toBeInstanceOf(FileOperationException::class);
        expect($missingResult->getDomainErrorCode())->toBe(FileOperationError::FILE_NOT_FOUND);
    });

    it('retrieves lastModified, visibility, and publicUrl on file', function (): void {
        // GIVEN
        Storage::disk('local')->put('meta.txt', 'content');
        $strategy = resolve(LocalStorageStrategy::class);
        $disk     = new Disk('local', 'local');
        $path     = new Path('/meta.txt');

        // WHEN
        $timestamp  = $strategy->lastModified($disk, $path);
        $visibility = $strategy->visibility($disk, $path);
        $url        = $strategy->publicUrl($disk, $path);

        // THEN
        expect($timestamp)->toBeInt();
        expect($visibility)->toBeString();
        expect($url)->toBeString();
    });

    it('returns OperationNotSupportedException when getting mimeType on directory', function (): void {
        // GIVEN
        Storage::disk('local')->makeDirectory('my_dir');
        $strategy = resolve(LocalStorageStrategy::class);
        $disk     = new Disk('local', 'local');
        $dirPath  = new Path('/my_dir');

        // WHEN
        $mimeResult = $strategy->mimeType($disk, $dirPath);

        // THEN
        expect($mimeResult)->toBeInstanceOf(OperationNotSupportedException::class);
        expect($mimeResult->getDomainErrorCode())->toBe(FileOperationError::OPERATION_NOT_SUPPORTED);
    });

    it('moves and copies files and directories correctly', function (): void {
        // GIVEN
        Storage::disk('local')->put('src.txt', 'copy me');
        Storage::disk('local')->makeDirectory('src_dir');
        Storage::disk('local')->put('src_dir/inner.txt', 'inside');
        $strategy = resolve(LocalStorageStrategy::class);
        $disk     = new Disk('local', 'local');

        // WHEN
        $copyFileResult = $strategy->copyFile($disk, 'src.txt', 'copy.txt');
        $moveFileResult = $strategy->moveFile($disk, 'copy.txt', 'moved.txt');
        $copyDirResult  = $strategy->copyDirectory($disk, 'src_dir', 'copy_dir');
        $moveDirResult  = $strategy->moveDirectory($disk, 'copy_dir', 'moved_dir');

        // THEN
        expect($copyFileResult)->toBeTrue();
        expect($moveFileResult)->toBeTrue();
        expect($copyDirResult)->toBeTrue();
        expect($moveDirResult)->toBeTrue();
        expect(Storage::disk('local')->exists('moved.txt'))->toBeTrue();
        expect(Storage::disk('local')->exists('moved_dir/inner.txt'))->toBeTrue();
    });

    it('lists contents in batch and checks subdirectories', function (): void {
        // GIVEN
        Storage::disk('local')->put('batch_test/test.txt', 'batch content');
        Storage::disk('local')->makeDirectory('batch_test/inner');
        $strategy = resolve(LocalStorageStrategy::class);
        $disk     = new Disk('local', 'local');

        // WHEN
        $listing      = $strategy->listContents($disk, 'batch_test');
        $hasSubdirs   = $strategy->hasSubdirectories($disk, 'batch_test');
        $emptySubdirs = $strategy->hasSubdirectories($disk, 'batch_test/inner');

        // THEN
        expect($listing)->toBeArray();
        expect($listing)->not->toBeEmpty();
        expect($hasSubdirs)->toBeTrue();
        expect($emptySubdirs)->toBeFalse();
    });

    it('performs basic file and directory management operations', function (): void {
        // GIVEN
        $strategy = resolve(LocalStorageStrategy::class);
        $disk     = new Disk('local', 'local');

        // WHEN
        $putResult        = $strategy->put($disk, 'test_put.txt', 'hello put');
        $makeDirResult    = $strategy->makeDirectory($disk, 'test_dir');
        $deleteFileResult = $strategy->delete($disk, 'test_put.txt');
        $deleteDirResult  = $strategy->deleteDirectory($disk, 'test_dir');

        // THEN
        expect($putResult)->toBeTrue();
        expect($makeDirResult)->toBeTrue();
        expect($deleteFileResult)->toBeTrue();
        expect($deleteDirResult)->toBeTrue();
    });

    it('checks exists isDirectory and isFile accurately', function (): void {
        // GIVEN
        Storage::disk('local')->put('file_check.txt', 'sample');
        Storage::disk('local')->makeDirectory('dir_check');
        $strategy = resolve(LocalStorageStrategy::class);
        $disk     = new Disk('local', 'local');
        $rootPath = new Path('/');
        $filePath = new Path('/file_check.txt');
        $dirPath  = new Path('/dir_check');
        $missing  = new Path('/missing.txt');

        // WHEN
        $rootExists    = $strategy->exists($disk, $rootPath);
        $fileExists    = $strategy->exists($disk, $filePath);
        $dirExists     = $strategy->exists($disk, $dirPath);
        $missingExists = $strategy->exists($disk, $missing);

        $rootIsDir     = $strategy->isDirectory($disk, $rootPath);
        $dirIsDir      = $strategy->isDirectory($disk, $dirPath);
        $fileIsDir     = $strategy->isDirectory($disk, $filePath);

        $rootIsFile    = $strategy->isFile($disk, $rootPath);
        $fileIsFile    = $strategy->isFile($disk, $filePath);
        $dirIsFile     = $strategy->isFile($disk, $dirPath);

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

    it('retrieves file size and directory listings', function (): void {
        // GIVEN
        Storage::disk('local')->put('listing_dir/f1.txt', '1234');
        Storage::disk('local')->put('listing_dir/sub/f2.txt', '5678');
        $strategy = resolve(LocalStorageStrategy::class);
        $disk     = new Disk('local', 'local');

        // WHEN
        $size           = $strategy->size($disk, 'listing_dir/f1.txt');
        $files          = $strategy->files($disk, 'listing_dir');
        $dirs           = $strategy->directories($disk, 'listing_dir');
        $allFiles       = $strategy->allFiles($disk, 'listing_dir');
        $allDirectories = $strategy->allDirectories($disk, 'listing_dir');
        $localPath      = $strategy->localPath($disk, 'listing_dir/f1.txt');

        // THEN
        expect($size)->toBe(4);
        expect($files)->toBeArray();
        expect($dirs)->toBeArray();
        expect($allFiles)->toBeArray();
        expect($allDirectories)->toBeArray();
        expect($localPath)->toBeString();
    });

    it('reads and writes streams correctly', function (): void {
        // GIVEN
        Storage::disk('local')->put('stream_src.txt', 'stream content test');
        $strategy  = resolve(LocalStorageStrategy::class);
        $disk      = new Disk('local', 'local');
        $srcPath   = new Path('/stream_src.txt');
        $destPath  = new Path('/stream_dest.txt');

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
        expect($strategy->get($disk, 'stream_dest.txt'))->toBe('stream content test');
    });
})->covers(LocalStorageStrategy::class);
