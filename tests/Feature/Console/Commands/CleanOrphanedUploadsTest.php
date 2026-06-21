<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Lib\Upload\UploadPathResolver;
use Kwaadpepper\LaravelStorageManager\Lib\Upload\UploadSessionService;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadId;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadMetadata;

describe('CleanOrphanedUploadsCommand', function (): void {
        assert($this instanceof \Tests\TestCase);
    beforeEach(function (): void {
        assert($this instanceof \Tests\TestCase);
        Storage::fake('temp_disk');
        Config::set('storage-manager.upload.temp_disk', 'temp_disk');
        Config::set('storage-manager.upload.temp_path', 'temp_uploads');
    });

    it('cleans up orphaned uploads older than 24 hours', function (): void {
        assert($this instanceof \Tests\TestCase);
        // Given
        $sessionService = resolve(UploadSessionService::class);
        $pathResolver = resolve(UploadPathResolver::class);

        // Create an old session
        $oldUploadId = UploadId::generate();
        $oldCreatedAt = CarbonImmutable::now()->subHours(25);
        $sessionService->createSession($oldUploadId, new UploadMetadata($oldUploadId, 'old.txt', 1, 100, $oldCreatedAt));

        // Create a new session
        $newUploadId = UploadId::generate();
        $sessionService->createSession($newUploadId, new UploadMetadata($newUploadId, 'new.txt', 1, 100, CarbonImmutable::now()));

        // Back-date the old session folder by 25 hours
        $oldDirAbsPath = $pathResolver->getAbsolutePath($pathResolver->getUploadDir($oldUploadId));
        $oldTime = $oldCreatedAt->timestamp;
        touch($oldDirAbsPath, $oldTime);
        touch($pathResolver->getAbsolutePath($pathResolver->getMetadataPath($oldUploadId)), $oldTime);

        // When
        $action = $this->artisan('sm:clean-orphaned-uploads');

        // Then
        $action->expectsOutput("Cleaned '1' orphaned upload directories.")
            ->assertExitCode(0)
            ->run();

        // Verify old session is gone
        expect($sessionService->sessionExists($oldUploadId))->toBeFalse()
            // Verify new session remains
            ;
        expect($sessionService->sessionExists($newUploadId))->toBeTrue();
    });
});
