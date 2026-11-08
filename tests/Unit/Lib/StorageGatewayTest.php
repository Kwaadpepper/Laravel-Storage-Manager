<?php

declare(strict_types=1);

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Exception\StorageConnectionException;
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

    it('resolves existing file and its properties', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $gateway = resolve(StorageGateway::class);
        $disk    = new Disk('local', 'local');

        $storage->put('docs/readme.txt', 'Readme content');

        // WHEN
        $resolved = $gateway->resolveExistingFile($disk, 'docs/readme.txt');

        // THEN
        expect($resolved)->toBeInstanceOf(ResolvedFile::class);
        expect($resolved->disk->name)->toBe('local');
        expect($resolved->path->value)->toBe('/docs/readme.txt');
        expect($resolved->properties)->toBeInstanceOf(FilePathProperties::class);
        expect($resolved->properties->basename)->toBe('readme.txt');
        expect($resolved->properties->extension)->toBe('txt');
        expect($resolved->properties->size)->toBe(strlen('Readme content'));
    });

    it('throws FileOperationException when file does not exist in resolveExistingFile', function (): void {
        // GIVEN
        $gateway = resolve(StorageGateway::class);
        $disk    = new Disk('local', 'local');

        // WHEN
        $action = fn (): ResolvedFile => $gateway->resolveExistingFile($disk, 'non_existent.txt');

        // THEN
        expect($action)->toThrow(FileOperationException::class);
    });

    it('throws FileOperationException when path is a directory in resolveExistingFile', function (): void {
        // GIVEN
        $storage = Storage::disk('local');
        $gateway = resolve(StorageGateway::class);
        $disk    = new Disk('local', 'local');

        $storage->makeDirectory('a_folder');

        // WHEN
        $action = fn (): ResolvedFile => $gateway->resolveExistingFile($disk, 'a_folder');

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
        $fsFromObj = $gateway->disk($diskObj);

        // THEN
        expect($fsFromObj)->toBeInstanceOf(Filesystem::class);
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

    it('falls back to parent scanning when directoryExists throws an exception on FilesystemAdapter', function (): void {
        // GIVEN
        Storage::disk('local')->makeDirectory('fallback_dir');
        $adapter = \Mockery::mock(FilesystemAdapter::class);
        $adapter->shouldReceive('directoryExists')
            ->with('fallback_dir')
            ->andThrow(new \RuntimeException('Connection timeout'));
        $adapter->shouldReceive('directories')
            ->with('')
            ->andReturn(['fallback_dir']);
        Storage::set('local', $adapter);

        $gateway = resolve(StorageGateway::class);
        $disk    = new Disk('local', 'local');
        $path    = new Path('/fallback_dir');

        // WHEN
        $result = $gateway->isDirectory($disk, $path);

        // THEN
        expect($result)->toBeTrue();
    });

    it('delegates supportsDirectoryMetadata to resolved strategy', function (): void {
        // GIVEN
        $gateway   = resolve(StorageGateway::class);
        $localDisk = new Disk('local', 'local');
        $s3Disk    = new Disk('s3', 's3');

        // WHEN
        $localSupported = $gateway->supportsDirectoryMetadata($localDisk);
        $s3Supported    = $gateway->supportsDirectoryMetadata($s3Disk);

        // THEN
        expect($localSupported)->toBeTrue();
        expect($s3Supported)->toBeFalse();
    });

    it('delegates calculateDiskUsedBytes to resolved strategy', function (): void {
        // GIVEN
        Storage::disk('local')->put('bytes_test.txt', '12345678');
        $gateway   = resolve(StorageGateway::class);
        $localDisk = new Disk('local', 'local');

        // WHEN
        $usedBytes = $gateway->calculateDiskUsedBytes($localDisk);

        // THEN
        expect($usedBytes)->toBe(8);
    });

    it('delegates getDiskStats to resolved strategy', function (): void {
        // GIVEN
        $gateway   = resolve(StorageGateway::class);
        $localDisk = new Disk('local', 'local');
        $s3Disk    = new Disk('s3', 's3');

        // WHEN
        $localStats = $gateway->getDiskStats($localDisk);
        $s3Stats    = $gateway->getDiskStats($s3Disk);

        // THEN
        expect($localStats->mode)->toBeIn(['partition', 'folder']);
        expect($s3Stats->mode)->toBe('unlimited');
    });

    it('returns null on lastModified and visibility for virtual directory when driver does not support directory metadata', function (): void {
        // GIVEN
        Storage::fake('s3');
        Storage::disk('s3')->makeDirectory('s3_dir');
        $gateway = resolve(StorageGateway::class);
        $s3Disk  = new Disk('s3', 's3');
        $dirPath = new Path('/s3_dir');

        // WHEN
        $lastModified = $gateway->lastModified($s3Disk, $dirPath);
        $visibility   = $gateway->visibility($s3Disk, $dirPath);

        // THEN
        expect($lastModified)->toBeNull();
        expect($visibility)->toBeNull();
    });

    it('returns null on publicUrl when driver does not support public urls', function (): void {
        // GIVEN
        Storage::fake('ftp');
        Config::set('filesystems.disks.ftp.url', null);
        $gateway = resolve(StorageGateway::class);
        $ftpDisk = new Disk('ftp', 'ftp');
        $path    = new Path('/file.txt');

        // WHEN
        $url = $gateway->publicUrl($ftpDisk, $path);

        // THEN
        expect($url)->toBeNull();
    });

    it('delegates supportsPublicUrl to resolved strategy', function (): void {
        // GIVEN
        $gateway   = resolve(StorageGateway::class);
        $localDisk = new Disk('local', 'local');
        $s3Disk    = new Disk('s3', 's3');
        $ftpDisk   = new Disk('ftp', 'ftp');
        Config::set('filesystems.disks.ftp.url', null);

        // WHEN
        $localSupported = $gateway->supportsPublicUrl($localDisk);
        $s3Supported    = $gateway->supportsPublicUrl($s3Disk);
        $ftpSupported   = $gateway->supportsPublicUrl($ftpDisk);

        // THEN
        expect($localSupported)->toBeTrue();
        expect($s3Supported)->toBeTrue();
        expect($ftpSupported)->toBeFalse();
    });

    it('returns null on mimeType for directory path', function (): void {
        // GIVEN
        Storage::disk('local')->makeDirectory('mime_dir');
        $gateway   = resolve(StorageGateway::class);
        $localDisk = new Disk('local', 'local');
        $dirPath   = new Path('/mime_dir');

        // WHEN
        $mime = $gateway->mimeType($localDisk, $dirPath);

        // THEN
        expect($mime)->toBeNull();
    });

    it('propagates PERMISSION_DENIED on get when storage throws 403 forbidden', function (): void {
        // GIVEN
        $adapter = \Mockery::mock(FilesystemAdapter::class);
        $adapter->shouldReceive('get')
            ->with('forbidden.txt')
            ->andThrow(new class ('Access Denied') extends \RuntimeException
            {
                public function getStatusCode(): int
                {
                    return 403;
                }
            });
        Storage::set('local', $adapter);
        $gateway   = resolve(StorageGateway::class);
        $localDisk = new Disk('local', 'local');

        // WHEN
        $action = fn (): ?string => $gateway->get($localDisk, 'forbidden.txt');

        // THEN
        expect($action)->toThrow(FileOperationException::class, FileOperationError::PERMISSION_DENIED->message());
    });

    it('propagates StorageConnectionException on get when storage connection fails', function (): void {
        // GIVEN
        $adapter = \Mockery::mock(FilesystemAdapter::class);
        $adapter->shouldReceive('get')
            ->with('timeout.txt')
            ->andThrow(new \RuntimeException('Connection timed out'));
        Storage::set('local', $adapter);
        $gateway   = resolve(StorageGateway::class);
        $localDisk = new Disk('local', 'local');

        // WHEN
        $action = fn (): ?string => $gateway->get($localDisk, 'timeout.txt');

        // THEN
        expect($action)->toThrow(StorageConnectionException::class, FileOperationError::CONNECTION_ERROR->message());
    });

    it('propagates StorageConnectionException on lastModified, visibility, and mimeType when network times out on file', function (): void {
        // GIVEN
        $adapter = \Mockery::mock(FilesystemAdapter::class);
        $adapter->shouldReceive('directoryExists')
            ->andReturn(false);
        $adapter->shouldReceive('lastModified')
            ->with('timeout.txt')
            ->andThrow(new \RuntimeException('Connection timed out'));
        $adapter->shouldReceive('getVisibility')
            ->with('timeout.txt')
            ->andThrow(new \RuntimeException('Connection timed out'));
        $adapter->shouldReceive('mimeType')
            ->with('timeout.txt')
            ->andThrow(new \RuntimeException('Connection timed out'));
        Storage::set('local', $adapter);
        $gateway   = resolve(StorageGateway::class);
        $localDisk = new Disk('local', 'local');
        $path      = new Path('/timeout.txt');

        // WHEN
        $lastModifiedAction = fn (): ?int => $gateway->lastModified($localDisk, $path);
        $visibilityAction   = fn (): ?string => $gateway->visibility($localDisk, $path);
        $mimeTypeAction     = fn (): ?string => $gateway->mimeType($localDisk, $path);

        // THEN
        expect($lastModifiedAction)->toThrow(StorageConnectionException::class);
        expect($visibilityAction)->toThrow(StorageConnectionException::class);
        expect($mimeTypeAction)->toThrow(StorageConnectionException::class);
    });

    it('propagates StorageConnectionException on calculateDiskUsedBytes when listing fails', function (): void {
        // GIVEN
        $adapter = \Mockery::mock(FilesystemAdapter::class);
        $adapter->shouldReceive('getDriver')
            ->andThrow(new \RuntimeException('Connection timed out'));
        Storage::set('local', $adapter);
        $gateway   = resolve(StorageGateway::class);
        $localDisk = new Disk('local', 'local');

        // WHEN
        $action = fn (): int => $gateway->calculateDiskUsedBytes($localDisk);

        // THEN
        expect($action)->toThrow(StorageConnectionException::class);
    });

    it('performs allFiles, allDirectories, and putStream operations via gateway', function (): void {
        // GIVEN
        $gateway = resolve(StorageGateway::class);
        $disk    = new Disk('local', 'local');
        $stream  = fopen('php://temp', 'r+');
        fwrite($stream, 'raw stream content');
        rewind($stream);
        $path = new Path('/nested/stream_file.txt');

        // WHEN
        $gateway->makeDirectory($disk, 'nested/level2');
        $gateway->putStream($disk, $path, $stream);
        $allFiles = $gateway->allFiles($disk, 'nested');
        $allDirs  = $gateway->allDirectories($disk, 'nested');

        // THEN
        expect($allFiles)->toContain('nested/stream_file.txt');
        expect($allDirs)->toContain('nested/level2');
        expect($gateway->get($disk, 'nested/stream_file.txt'))->toBe('raw stream content');
    });
})->covers(StorageGateway::class);
