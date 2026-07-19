<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Enum\UploadStatus;
use Kwaadpepper\LaravelStorageManager\Exception\Upload\MissingChunkException;
use Kwaadpepper\LaravelStorageManager\Exception\Upload\MissingMetadataException;
use Kwaadpepper\LaravelStorageManager\Lib\Upload\UploadPathResolver;
use Kwaadpepper\LaravelStorageManager\Lib\Upload\UploadSessionService;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadId;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadMetadata;

describe('UploadSessionService', function (): void {
    beforeEach(function (): void {
        Storage::fake('temp_disk');
        Storage::fake('final_disk');
        Config::set('storage-manager.upload.temp_disk', 'temp_disk');
        Config::set('storage-manager.upload.temp_path', 'temp_uploads');

        $this->pathResolver   = resolve(UploadPathResolver::class);
        $this->sessionService = new UploadSessionService($this->pathResolver);
    });

    it('creates a session successfully', function (): void {
        // Given
        $uploadId = UploadId::generate();
        $metadata = new UploadMetadata($uploadId, 'test.txt', 1, 100, CarbonImmutable::now());

        // When
        $this->sessionService->createSession($uploadId, $metadata);

        // Then
        expect($this->sessionService->sessionExists($uploadId))->toBeTrue();

        $savedMetadata = $this->sessionService->readMetadata($uploadId);
        expect($savedMetadata)->not->toBeNull();
        expect($savedMetadata->uploadId->value)->toBe($uploadId->value);
    });

    it('stores a chunk successfully', function (): void {
        // Given
        $uploadId = UploadId::generate();
        $this->sessionService->createSession($uploadId, new UploadMetadata($uploadId, 'test.txt', 1, 100, CarbonImmutable::now()));
        $file = UploadedFile::fake()->createWithContent('chunk.part', 'dummy content');

        // When
        $this->sessionService->storeChunk($uploadId, 0, $file);

        // Then
        expect($this->sessionService->chunkExists($uploadId, 0))->toBeTrue();
    });

    it('reads and writes status correctly', function (): void {
        // Given
        $uploadId = UploadId::generate();
        $this->sessionService->createSession($uploadId, new UploadMetadata($uploadId, 'test.txt', 1, 100, CarbonImmutable::now()));

        // When
        $this->sessionService->writeStatus($uploadId, UploadStatus::ASSEMBLING, 50);
        $status = $this->sessionService->readStatus($uploadId);

        // Then
        expect($status)->not->toBeNull();
        expect($status->status)->toBe(UploadStatus::ASSEMBLING);
        expect($status->progress)->toBe(50);
    });

    it('throws MissingMetadataException on assemble if no metadata', function (): void {
        // Given
        $uploadId = UploadId::generate();

        // When
        $action = fn () => $this->sessionService->assembleAndTransfer($uploadId, new Path('/final/test.txt'), 'final_disk');

        // Then
        expect($action)->toThrow(MissingMetadataException::class);
    });

    it('throws MissingChunkException on assemble if chunk is missing', function (): void {
        // Given
        $uploadId = UploadId::generate();
        $this->sessionService->createSession($uploadId, new UploadMetadata($uploadId, 'test.txt', 2, 100, CarbonImmutable::now()));

        // Only store chunk 0, chunk 1 is missing
        $file = UploadedFile::fake()->createWithContent('chunk0.part', 'content 1');
        $this->sessionService->storeChunk($uploadId, 0, $file);

        // When
        $action = fn () => $this->sessionService->assembleAndTransfer($uploadId, new Path('/final/test.txt'), 'final_disk');

        // Then
        expect($action)->toThrow(MissingChunkException::class);
    });

    it('assembles chunks and transfers file successfully', function (): void {
        // Given
        $uploadId = UploadId::generate();
        $this->sessionService->createSession($uploadId, new UploadMetadata($uploadId, 'test.txt', 2, 100, CarbonImmutable::now()));

        $file1 = UploadedFile::fake()->createWithContent('chunk0.part', 'Hello ');
        $this->sessionService->storeChunk($uploadId, 0, $file1);

        $file2 = UploadedFile::fake()->createWithContent('chunk1.part', 'World!');
        $this->sessionService->storeChunk($uploadId, 1, $file2);

        // When
        $this->sessionService->assembleAndTransfer($uploadId, new Path('/final/test.txt'), 'final_disk');

        // Then
        // Verify it was assembled and transferred
        expect(Storage::disk('final_disk')->exists('/final/test.txt'))->toBeTrue();
        expect(Storage::disk('final_disk')->get('/final/test.txt'))->toBe('Hello World!');

        // Verify cleanup
        expect($this->sessionService->sessionExists($uploadId))->toBeFalse();
    });
});
