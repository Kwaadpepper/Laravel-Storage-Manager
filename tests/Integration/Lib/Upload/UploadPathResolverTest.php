<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Lib\Upload\UploadPathResolver;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadId;
use Kwaadpepper\LaravelStorageManager\Repository\ConfigRepository;

describe('UploadPathResolver', function (): void {
    beforeEach(function (): void {
        $this->configRepository = resolve(ConfigRepository::class);
    });

    it('uses fallback local temp disk if none configured', function (): void {
        // Given
        $config = config('storage-manager');
        $config['upload']['temp_disk'] = null;
        $configRepository = new ConfigRepository($config, config('storage-manager::static-config'));
        $pathResolver = new UploadPathResolver($configRepository);

        // When
        $disk = $pathResolver->getDisk();

        // Then
        // On the local driver, the root path will be sys_get_temp_dir()
        expect($disk->path(''))->toStartWith(sys_get_temp_dir());
    });

    it('uses configured disk', function (): void {
        // Given
        Storage::fake('fake_disk');
        $config = config('storage-manager');
        $config['upload']['temp_disk'] = 'fake_disk';
        $configRepository = new ConfigRepository($config, config('storage-manager::static-config'));
        $pathResolver = new UploadPathResolver($configRepository);

        // When
        $disk = $pathResolver->getDisk();

        // Then
        // Ensure it's using the correct fake disk
        expect($disk)->toBe(Storage::disk('fake_disk'));
    });

    it('generates correct paths for a session', function (): void {
        // Given
        $config = config('storage-manager');
        $config['upload']['temp_path'] = 'lsm_uploads_temp';
        $configRepository = new ConfigRepository($config, config('storage-manager::static-config'));
        $pathResolver = new UploadPathResolver($configRepository);
        $uploadId = new UploadId('01903ba4-9fc2-75d8-9174-cd5ebbd272b2');

        // When
        $basePath = $pathResolver->getBasePath();
        $uploadDir = $pathResolver->getUploadDir($uploadId);
        $metadataPath = $pathResolver->getMetadataPath($uploadId);
        $statusPath = $pathResolver->getStatusPath($uploadId);
        $chunkPath = $pathResolver->getChunkPath($uploadId, 5);
        $assembledFilePath = $pathResolver->getAssembledFilePath($uploadId);

        // Then
        expect($basePath)->toBe('lsm_uploads_temp')
            ;
        expect($uploadDir)->toBe('lsm_uploads_temp/01903ba4-9fc2-75d8-9174-cd5ebbd272b2')
            ;
        expect($metadataPath)->toBe('lsm_uploads_temp/01903ba4-9fc2-75d8-9174-cd5ebbd272b2/metadata.json')
            ;
        expect($statusPath)->toBe('lsm_uploads_temp/01903ba4-9fc2-75d8-9174-cd5ebbd272b2/status.json')
            ;
        expect($chunkPath)->toBe('lsm_uploads_temp/01903ba4-9fc2-75d8-9174-cd5ebbd272b2/chunk_5')
            ;
        expect($assembledFilePath)->toBe('lsm_uploads_temp/01903ba4-9fc2-75d8-9174-cd5ebbd272b2/assembled_file');
    });
});
