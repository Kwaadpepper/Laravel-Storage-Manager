<?php

declare(strict_types=1);

use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Lib\Upload\UploadPathResolver;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadId;

describe('UploadPathResolver', function (): void {
    beforeEach(function (): void {
        $this->configRepository = resolve(StorageManagerConfig::class);
    });

    it('uses fallback local temp disk if none configured', function (): void {
        // GIVEN
        $config                        = config('storage-manager');
        $config['upload']['temp_disk'] = null;
        $configRepository              = StorageManagerConfig::fromArray($config, config('storage-manager::static-config'));
        $pathResolver                  = new UploadPathResolver($configRepository, resolve(FilesystemFactory::class));

        // WHEN
        $disk = $pathResolver->getDisk();

        // THEN
        expect($disk->path(''))->toStartWith(sys_get_temp_dir());
    });

    it('uses configured disk', function (): void {
        // GIVEN
        Storage::fake('fake_disk');
        $config                        = config('storage-manager');
        $config['upload']['temp_disk'] = 'fake_disk';
        $configRepository              = StorageManagerConfig::fromArray($config, config('storage-manager::static-config'));
        $pathResolver                  = new UploadPathResolver($configRepository, resolve(FilesystemFactory::class));

        // WHEN
        $disk = $pathResolver->getDisk();

        // THEN
        expect($disk)->toBe(Storage::disk('fake_disk'));
    });

    it('generates correct paths for a session', function (): void {
        // GIVEN
        $config                        = config('storage-manager');
        $config['upload']['temp_path'] = 'lsm_uploads_temp';
        $configRepository              = StorageManagerConfig::fromArray($config, config('storage-manager::static-config'));
        $pathResolver                  = new UploadPathResolver($configRepository, resolve(FilesystemFactory::class));
        $uploadId                      = new UploadId('01903ba4-9fc2-75d8-9174-cd5ebbd272b2');

        // WHEN
        $basePath          = $pathResolver->getBasePath();
        $uploadDir         = $pathResolver->getUploadDir($uploadId);
        $metadataPath      = $pathResolver->getMetadataPath($uploadId);
        $statusPath        = $pathResolver->getStatusPath($uploadId);
        $lockPath          = $pathResolver->getLockPath($uploadId);
        $chunkPath         = $pathResolver->getChunkPath($uploadId, 5);
        $assembledFilePath = $pathResolver->getAssembledFilePath($uploadId);

        // THEN
        expect($basePath)->toStartWith('lsm_uploads_temp');
        expect($uploadDir)->toBe($basePath . '/' . $uploadId->value);
        expect($metadataPath)->toBe($basePath . '/' . $uploadId->value . '/metadata.json');
        expect($statusPath)->toBe($basePath . '/' . $uploadId->value . '/status.json');
        expect($lockPath)->toBe($basePath . '/' . $uploadId->value . '/status.lock');
        expect($chunkPath)->toBe($basePath . '/' . $uploadId->value . '/chunk_5');
        expect($assembledFilePath)->toBe($basePath . '/' . $uploadId->value . '/assembled_file');
    });
})->covers(UploadPathResolver::class);
