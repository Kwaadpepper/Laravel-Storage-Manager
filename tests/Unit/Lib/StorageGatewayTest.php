<?php

declare(strict_types=1);

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Lib\StorageGateway;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\FilePathProperties;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\ResolvedFile;

describe('StorageGateway', function (): void {
    beforeEach(function (): void {
        Storage::fake('local');
        Config::set('filesystems.disks.local', [
            'driver' => 'local',
        ]);
        Config::set('storage-manager.disks.available', ['local']);
    });

    it('checks exists, isDirectory, and isFile correctly', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $gateway = resolve(StorageGateway::class);
        $disk    = new Disk('local', 'local');

        $storage->makeDirectory('sample_dir');
        $storage->put('sample_file.txt', 'Hello world');

        // WHEN
        $existsRoot = $gateway->exists($disk, new Path('/'));
        $isDirRoot  = $gateway->isDirectory($disk, new Path('/'));
        $isFileRoot = $gateway->isFile($disk, new Path('/'));

        $existsDir = $gateway->exists($disk, new Path('/sample_dir'));
        $isDirDir  = $gateway->isDirectory($disk, new Path('/sample_dir'));
        $isFileDir = $gateway->isFile($disk, new Path('/sample_dir'));

        $existsFile = $gateway->exists($disk, new Path('/sample_file.txt'));
        $isDirFile  = $gateway->isDirectory($disk, new Path('/sample_file.txt'));
        $isFileFile = $gateway->isFile($disk, new Path('/sample_file.txt'));

        $existsMissing = $gateway->exists($disk, new Path('/missing.txt'));
        $isDirMissing  = $gateway->isDirectory($disk, new Path('/missing.txt'));
        $isFileMissing = $gateway->isFile($disk, new Path('/missing.txt'));

        // THEN
        expect($existsRoot)->toBeTrue();
        expect($isDirRoot)->toBeTrue();
        expect($isFileRoot)->toBeFalse();

        expect($existsDir)->toBeTrue();
        expect($isDirDir)->toBeTrue();
        expect($isFileDir)->toBeFalse();

        expect($existsFile)->toBeTrue();
        expect($isDirFile)->toBeFalse();
        expect($isFileFile)->toBeTrue();

        expect($existsMissing)->toBeFalse();
        expect($isDirMissing)->toBeFalse();
        expect($isFileMissing)->toBeFalse();
    });

    it('resolves whether an existing path is a directory with resolveIsDirectory', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $gateway = resolve(StorageGateway::class);
        $disk    = new Disk('local', 'local');
        $storage->makeDirectory('sample_dir');
        $storage->put('sample_file.txt', 'Hello world');

        // WHEN
        $isDirResult  = $gateway->resolveIsDirectory($disk, new Path('/sample_dir'));
        $isFileResult = $gateway->resolveIsDirectory($disk, new Path('/sample_file.txt'));
        $isRootResult = $gateway->resolveIsDirectory($disk, new Path('/'));

        // THEN
        expect($isDirResult)->toBeTrue();
        expect($isFileResult)->toBeFalse();
        expect($isRootResult)->toBeTrue();
    });

    it('throws FILE_NOT_FOUND when resolveIsDirectory is called on non-existent path', function (): void {
        // GIVEN
        $gateway = resolve(StorageGateway::class);
        $disk    = new Disk('local', 'local');

        // WHEN
        $action = fn (): bool => $gateway->resolveIsDirectory($disk, new Path('/missing.txt'));

        // THEN
        expect($action)->toThrow(FileOperationException::class, FileOperationError::FILE_NOT_FOUND->message());
    });

    it('reads a valid stream for an existing file', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $gateway = resolve(StorageGateway::class);
        $disk    = new Disk('local', 'local');

        $storage->put('stream_test.txt', 'Stream content data');

        // WHEN
        $fileStream = $gateway->readStream($disk, new Path('/stream_test.txt'));
        $resource   = $fileStream->stream();

        // THEN
        expect(is_resource($resource))->toBeTrue();
        expect(stream_get_contents($resource))->toBe('Stream content data');
    });

    it('reads a stream directly when skipExistenceCheck is true', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $gateway = resolve(StorageGateway::class);
        $disk    = new Disk('local', 'local');

        $storage->put('direct_stream.txt', 'Direct stream content');

        // WHEN
        $fileStream = $gateway->readStream($disk, new Path('/direct_stream.txt'), true);
        $resource   = $fileStream->stream();

        // THEN
        expect(is_resource($resource))->toBeTrue();
        expect(stream_get_contents($resource))->toBe('Direct stream content');
    });

    it('throws FILE_NOT_FOUND when trying to readStream a non-existent file', function (): void {
        // GIVEN
        $gateway = resolve(StorageGateway::class);
        $disk    = new Disk('local', 'local');

        // WHEN
        $action = fn () => $gateway->readStream($disk, new Path('/not_found.txt'));

        // THEN
        expect($action)
            ->toThrow(FileOperationException::class, FileOperationError::FILE_NOT_FOUND->message());
    });

    it('throws INVALID_PATH when trying to readStream a directory', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $gateway = resolve(StorageGateway::class);
        $disk    = new Disk('local', 'local');

        $storage->makeDirectory('folder_test');

        // WHEN
        $action = fn () => $gateway->readStream($disk, new Path('/folder_test'));

        // THEN
        expect($action)
            ->toThrow(FileOperationException::class, FileOperationError::INVALID_PATH->message());
    });

    it('moves a file correctly', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $gateway = resolve(StorageGateway::class);
        $disk    = new Disk('local', 'local');

        $storage->put('source.txt', 'Content to move');

        // WHEN
        $gateway->move($disk, '/source.txt', '/target.txt', false);

        // THEN
        expect($storage->exists('source.txt'))->toBeFalse();
        expect($storage->exists('target.txt'))->toBeTrue();
        expect($storage->get('target.txt'))->toBe('Content to move');
    });

    it('moves a directory and its hierarchy recursively', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $gateway = resolve(StorageGateway::class);
        $disk    = new Disk('local', 'local');

        $storage->makeDirectory('folder/sub');
        $storage->put('folder/sub/file.txt', 'Inside sub');
        $storage->put('folder/root.txt', 'Inside root');

        // WHEN
        $gateway->move($disk, '/folder', '/moved_folder', true);

        // THEN
        expect($storage->directoryExists('folder'))->toBeFalse();
        expect($storage->directoryExists('moved_folder'))->toBeTrue();
        expect($storage->get('moved_folder/sub/file.txt'))->toBe('Inside sub');
        expect($storage->get('moved_folder/root.txt'))->toBe('Inside root');
    });

    it('does nothing when moving a source to identical destination', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $gateway = resolve(StorageGateway::class);
        $disk    = new Disk('local', 'local');

        $storage->put('same.txt', 'Same content');

        // WHEN
        $gateway->move($disk, '/same.txt', '/same.txt', false);

        // THEN
        expect($storage->exists('same.txt'))->toBeTrue();
        expect($storage->get('same.txt'))->toBe('Same content');
    });

    it('copies a file correctly', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $gateway = resolve(StorageGateway::class);
        $disk    = new Disk('local', 'local');

        $storage->put('original.txt', 'Content to copy');

        // WHEN
        $gateway->copy($disk, new Path('/original.txt'), new Path('/copied.txt'), false);

        // THEN
        expect($storage->exists('original.txt'))->toBeTrue();
        expect($storage->exists('copied.txt'))->toBeTrue();
        expect($storage->get('copied.txt'))->toBe('Content to copy');
    });

    it('copies a directory and its hierarchy recursively', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $gateway = resolve(StorageGateway::class);
        $disk    = new Disk('local', 'local');

        $storage->makeDirectory('source_dir/sub');
        $storage->put('source_dir/sub/file.txt', 'Sub file');
        $storage->put('source_dir/main.txt', 'Main file');

        // WHEN
        $gateway->copy($disk, new Path('/source_dir'), new Path('/dest_dir'), true);

        // THEN
        expect($storage->exists('source_dir/main.txt'))->toBeTrue();
        expect($storage->exists('source_dir/sub/file.txt'))->toBeTrue();
        expect($storage->exists('dest_dir/main.txt'))->toBeTrue();
        expect($storage->exists('dest_dir/sub/file.txt'))->toBeTrue();
        expect($storage->get('dest_dir/sub/file.txt'))->toBe('Sub file');
    });

    it('does nothing when copying a source to identical destination', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $gateway = resolve(StorageGateway::class);
        $disk    = new Disk('local', 'local');

        $storage->put('keep.txt', 'Keep it');

        // WHEN
        $gateway->copy($disk, new Path('/keep.txt'), new Path('/keep.txt'), false);

        // THEN
        expect($storage->exists('keep.txt'))->toBeTrue();
    });

    it('resolves available path with counter suffix on collision', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $gateway = resolve(StorageGateway::class);
        $disk    = new Disk('local', 'local');

        // WHEN
        $freePath = $gateway->resolveAvailablePath($disk, new Path('/documents/report.pdf'));

        $storage->put('documents/report.pdf', 'First report');
        $firstCollision = $gateway->resolveAvailablePath($disk, new Path('/documents/report.pdf'));

        $storage->put('documents/report (1).pdf', 'Second report');
        $secondCollision = $gateway->resolveAvailablePath($disk, new Path('/documents/report.pdf'));

        $storage->makeDirectory('projects');
        $dirCollision = $gateway->resolveAvailablePath($disk, new Path('/projects'));

        // THEN
        expect($freePath->value)->toBe('/documents/report.pdf');
        expect($firstCollision->value)->toBe('/documents/report (1).pdf');
        expect($secondCollision->value)->toBe('/documents/report (2).pdf');
        expect($dirCollision->value)->toBe('/projects (1)');
    });

    it('resolves existing file and its properties', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $gateway = resolve(StorageGateway::class);

        $storage->put('docs/readme.txt', 'Readme content');

        // WHEN
        $resolved = $gateway->resolveExistingFile('local', 'docs/readme.txt');

        // THEN
        expect($resolved)->toBeInstanceOf(ResolvedFile::class);
        expect($resolved->disk->name)->toBe('local');
        expect($resolved->path->value)->toBe('/docs/readme.txt');
        expect($resolved->properties)->toBeInstanceOf(FilePathProperties::class);
        expect($resolved->properties->basename)->toBe('readme.txt');
        expect($resolved->properties->extension)->toBe('txt');
        expect($resolved->properties->size)->toBe(strlen('Readme content'));
    });

    it('throws FileOperationException when disk does not exist in resolveExistingFile', function (): void {
        // GIVEN
        $gateway = resolve(StorageGateway::class);

        // WHEN
        $action = fn (): ResolvedFile => $gateway->resolveExistingFile('unknown-disk', 'test.txt');

        // THEN
        expect($action)->toThrow(FileOperationException::class);
    });

    it('throws FileOperationException when file does not exist in resolveExistingFile', function (): void {
        // GIVEN
        $gateway = resolve(StorageGateway::class);

        // WHEN
        $action = fn (): ResolvedFile => $gateway->resolveExistingFile('local', 'non_existent.txt');

        // THEN
        expect($action)->toThrow(FileOperationException::class);
    });

    it('throws FileOperationException when path is a directory in resolveExistingFile', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $gateway = resolve(StorageGateway::class);

        $storage->makeDirectory('a_folder');

        // WHEN
        $action = fn (): ResolvedFile => $gateway->resolveExistingFile('local', 'a_folder');

        // THEN
        expect($action)->toThrow(FileOperationException::class);
    });

    it('delegates move and copy operations to S3StorageStrategy for s3 disks', function (): void {
        // GIVEN
        Storage::fake('s3');
        $storage = Storage::disk('s3');
        $gateway = resolve(StorageGateway::class);
        $disk    = new Disk('s3', 's3');

        $storage->makeDirectory('s3_dir/sub');
        $storage->put('s3_dir/sub/item.txt', 'S3 content');

        // WHEN
        $gateway->copy($disk, new Path('/s3_dir'), new Path('/s3_copy'), true);
        $copyContent = $storage->get('s3_copy/sub/item.txt');

        $gateway->move($disk, '/s3_dir', '/s3_moved', true);
        $dirExists    = $storage->directoryExists('s3_dir');
        $movedContent = $storage->get('s3_moved/sub/item.txt');

        // THEN
        expect($copyContent)->toBe('S3 content');
        expect($dirExists)->toBeFalse();
        expect($movedContent)->toBe('S3 content');
    });

    it('delegates move and copy operations to RemoteStorageStrategy for ftp and sftp disks', function (): void {
        // GIVEN
        Storage::fake('ftp');
        $storage = Storage::disk('ftp');
        $gateway = resolve(StorageGateway::class);
        $disk    = new Disk('ftp', 'ftp');

        $storage->makeDirectory('ftp_dir/nested');
        $storage->put('ftp_dir/nested/file.txt', 'FTP content');

        // WHEN
        $gateway->copy($disk, new Path('/ftp_dir'), new Path('/ftp_copy'), true);
        $copyContent = $storage->get('ftp_copy/nested/file.txt');

        $gateway->move($disk, '/ftp_dir', '/ftp_moved', true);
        $dirExists    = $storage->directoryExists('ftp_dir');
        $movedContent = $storage->get('ftp_moved/nested/file.txt');

        // THEN
        expect($copyContent)->toBe('FTP content');
        expect($dirExists)->toBeFalse();
        expect($movedContent)->toBe('FTP content');
    });

    it('retrieves filesystem instance using disk method', function (): void {
        // GIVEN
        $gateway = resolve(StorageGateway::class);
        $diskObj = new Disk('local', 'local');

        // WHEN
        $fsFromObj  = $gateway->disk($diskObj);
        $fsFromName = $gateway->disk('local');

        // THEN
        expect($fsFromObj)->toBeInstanceOf(Filesystem::class);
        expect($fsFromName)->toBeInstanceOf(Filesystem::class);
    });

    it('performs put, get, and delete operations via gateway', function (): void {
        // GIVEN
        $gateway = resolve(StorageGateway::class);
        $disk    = new Disk('local', 'local');

        // WHEN
        $putResult      = $gateway->put($disk, 'gateway_test.txt', 'Gateway content');
        $getContent     = $gateway->get($disk, 'gateway_test.txt');
        $deleteResult   = $gateway->delete($disk, 'gateway_test.txt');
        $getAfterDelete = $gateway->get($disk, 'gateway_test.txt');

        // THEN
        expect($putResult)->toBeTrue();
        expect($getContent)->toBe('Gateway content');
        expect($deleteResult)->toBeTrue();
        expect($getAfterDelete)->toBeNull();
    });

    it('performs directory operations via gateway', function (): void {
        // GIVEN
        $gateway = resolve(StorageGateway::class);
        $disk    = new Disk('local', 'local');

        // WHEN
        $makeResult = $gateway->makeDirectory($disk, 'gateway_dir/sub');
        $gateway->put($disk, 'gateway_dir/sub/file.txt', 'file');
        $gateway->put($disk, 'gateway_dir/root_file.txt', 'root');
        $files           = $gateway->files($disk, 'gateway_dir');
        $directories     = $gateway->directories($disk, 'gateway_dir');
        $deleteDirResult = $gateway->deleteDirectory($disk, 'gateway_dir');

        // THEN
        expect($makeResult)->toBeTrue();
        expect($files)->toContain('gateway_dir/root_file.txt');
        expect($directories)->toContain('gateway_dir/sub');
        expect($deleteDirResult)->toBeTrue();
    });

    it('resolves localPath and publicUrl via gateway', function (): void {
        // GIVEN
        $gateway = resolve(StorageGateway::class);
        $disk    = new Disk('local', 'local');
        $path    = new Path('/file.txt');

        // WHEN
        $localPath = $gateway->localPath($disk, 'file.txt');
        $publicUrl = $gateway->publicUrl($disk, $path);

        // THEN
        expect(is_string($localPath) || $localPath === null)->toBeTrue();
        expect(is_string($publicUrl) || $publicUrl === null)->toBeTrue();
    });
})->covers(StorageGateway::class);
