<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Lib\Upload\UploadPathResolver;
use Kwaadpepper\LaravelStorageManager\Lib\Upload\UploadSessionService;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadId;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadMetadata;
use Tests\TestCase;

describe('CleanOrphanedUploadsCommand', function (): void {
    beforeEach(function (): void {
        assert($this instanceof TestCase);
        CarbonImmutable::setTestNow('2026-01-02 12:00:00');
        Storage::fake('temp_disk');
        Config::set('storage-manager.upload.temp_disk', 'temp_disk');
        Config::set('storage-manager.upload.temp_path', 'temp_uploads');
        App::forgetInstance(StorageManagerConfig::class);
    });

    afterEach(function (): void {
        CarbonImmutable::setTestNow();
    });

    it('cleans up orphaned uploads older than 24 hours', function (): void {
        assert($this instanceof TestCase);
        // GIVEN
        $sessionService = resolve(UploadSessionService::class);
        $pathResolver   = resolve(UploadPathResolver::class);

        $oldUploadId  = UploadId::generate();
        $oldCreatedAt = CarbonImmutable::now()->subHours(25);
        $sessionService->createSession($oldUploadId, new UploadMetadata($oldUploadId, 'old.txt', 1, 100, $oldCreatedAt));

        $newUploadId = UploadId::generate();
        $sessionService->createSession($newUploadId, new UploadMetadata($newUploadId, 'new.txt', 1, 100, CarbonImmutable::now()));

        $oldDirAbsPath = $pathResolver->getAbsolutePath($pathResolver->getUploadDir($oldUploadId));
        $oldTime       = $oldCreatedAt->timestamp;
        touch($oldDirAbsPath, $oldTime);
        touch($pathResolver->getAbsolutePath($pathResolver->getMetadataPath($oldUploadId)), $oldTime);
        clearstatcache();

        // WHEN
        $action = $this->artisan('sm:clean-orphaned-uploads');

        // THEN
        $action->expectsOutput("Cleaned '1' orphaned upload directories.")
            ->assertExitCode(0)
            ->run();

        expect($sessionService->sessionExists($oldUploadId))->toBeFalse();
        expect($sessionService->sessionExists($newUploadId))->toBeTrue();
    });
});
