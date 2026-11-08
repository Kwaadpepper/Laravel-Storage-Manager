<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Enum\DuplicatePolicy;
use Kwaadpepper\LaravelStorageManager\Enum\UploadStatus;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Exception\Upload\MissingChunkException;
use Kwaadpepper\LaravelStorageManager\Exception\Upload\MissingMetadataException;
use Kwaadpepper\LaravelStorageManager\Lib\Upload\UploadPathResolver;
use Kwaadpepper\LaravelStorageManager\Lib\Upload\UploadSessionService;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
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
        $this->sessionService = resolve(UploadSessionService::class);
    });

    it('creates a session successfully', function (): void {
        // GIVEN
        $uploadId = UploadId::generate();
        $metadata = new UploadMetadata($uploadId, 'test.txt', 1, 100, CarbonImmutable::now());

        // WHEN
        $this->sessionService->createSession($uploadId, $metadata);

        // THEN
        expect($this->sessionService->sessionExists($uploadId))->toBeTrue();

        $savedMetadata = $this->sessionService->readMetadata($uploadId);
        expect($savedMetadata)->not->toBeNull();
        expect($savedMetadata->uploadId->value)->toBe($uploadId->value);
    });

    it('stores a chunk successfully and updates progress', function (): void {
        // GIVEN
        $uploadId = UploadId::generate();
        $this->sessionService->createSession($uploadId, new UploadMetadata($uploadId, 'test.txt', 2, 100, CarbonImmutable::now()));
        $file = UploadedFile::fake()->createWithContent('chunk.part', 'dummy content');

        // WHEN
        $this->sessionService->storeChunk($uploadId, 0, $file);

        // THEN
        expect($this->sessionService->chunkExists($uploadId, 0))->toBeTrue();
        $status = $this->sessionService->readStatus($uploadId);
        expect($status)->not->toBeNull();
        expect($status->status)->toBe(UploadStatus::WAITING);
        expect($status->progress)->toBe(50);
    });

    it('reads and writes status correctly', function (): void {
        // GIVEN
        $uploadId = UploadId::generate();
        $this->sessionService->createSession($uploadId, new UploadMetadata($uploadId, 'test.txt', 1, 100, CarbonImmutable::now()));

        // WHEN
        $this->sessionService->writeStatus($uploadId, UploadStatus::ASSEMBLING, 50);
        $status = $this->sessionService->readStatus($uploadId);

        // THEN
        expect($status)->not->toBeNull();
        expect($status->status)->toBe(UploadStatus::ASSEMBLING);
        expect($status->progress)->toBe(50);
    });

    it('throws MissingMetadataException on assemble if no metadata', function (): void {
        // GIVEN
        $uploadId = UploadId::generate();

        // WHEN
        $action = function () use ($uploadId): void {
            $this->sessionService->assembleAndTransfer($uploadId, new Path('/final/test.txt'), new Disk('local', 'final_disk'));
        };

        // THEN
        expect($action)->toThrow(MissingMetadataException::class);
    });

    it('throws MissingChunkException on assemble if chunk is missing', function (): void {
        // GIVEN
        $uploadId = UploadId::generate();
        $this->sessionService->createSession($uploadId, new UploadMetadata($uploadId, 'test.txt', 2, 100, CarbonImmutable::now()));
        $file = UploadedFile::fake()->createWithContent('chunk0.part', 'content 1');
        $this->sessionService->storeChunk($uploadId, 0, $file);

        // WHEN
        $action = function () use ($uploadId): void {
            $this->sessionService->assembleAndTransfer($uploadId, new Path('/final/test.txt'), new Disk('local', 'final_disk'));
        };

        // THEN
        expect($action)->toThrow(MissingChunkException::class);
    });

    it('assembles chunks and transfers file successfully', function (): void {
        // GIVEN
        $uploadId = UploadId::generate();
        $this->sessionService->createSession($uploadId, new UploadMetadata($uploadId, 'test.txt', 2, 100, CarbonImmutable::now()));

        $file1 = UploadedFile::fake()->createWithContent('chunk0.part', 'Hello ');
        $this->sessionService->storeChunk($uploadId, 0, $file1);

        $file2 = UploadedFile::fake()->createWithContent('chunk1.part', 'World!');
        $this->sessionService->storeChunk($uploadId, 1, $file2);

        // WHEN
        $this->sessionService->assembleAndTransfer($uploadId, new Path('/final/test.txt'), new Disk('local', 'final_disk'));

        // THEN
        expect(Storage::disk('final_disk')->exists('/final/test.txt'))->toBeTrue();
        expect(Storage::disk('final_disk')->get('/final/test.txt'))->toBe('Hello World!');
        expect($this->sessionService->sessionExists($uploadId))->toBeFalse();
    });

    it('throws FileOperationException when completeUpload targets a read-only disk', function (): void {
        // GIVEN
        $uploadId = UploadId::generate();
        $this->sessionService->createSession($uploadId, new UploadMetadata($uploadId, 'ro.txt', 1, 5, CarbonImmutable::now()));

        $file = UploadedFile::fake()->createWithContent('chunk0.part', 'hello');
        $this->sessionService->storeChunk($uploadId, 0, $file);

        $readOnlyDisk = new Disk('local', 'final_disk', false, false, true);

        // WHEN
        $action = fn (): string => $this->sessionService->completeUpload(
            $uploadId,
            $readOnlyDisk,
            new Path('/'),
            'ro.txt'
        );

        // THEN
        expect($action)->toThrow(FileOperationException::class);
    });

    it('throws FileOperationException with DuplicatePolicy::ERROR when file already exists', function (): void {
        // GIVEN
        Storage::disk('final_disk')->put('test.txt', 'existing content');

        $uploadId = UploadId::generate();
        $this->sessionService->createSession($uploadId, new UploadMetadata($uploadId, 'test.txt', 1, 5, CarbonImmutable::now()));

        $file = UploadedFile::fake()->createWithContent('chunk0.part', 'hello');
        $this->sessionService->storeChunk($uploadId, 0, $file);

        $disk = new Disk('local', 'final_disk');

        // WHEN
        $action = fn (): string => $this->sessionService->completeUpload(
            $uploadId,
            $disk,
            new Path('/'),
            'test.txt',
            DuplicatePolicy::ERROR
        );

        // THEN
        expect($action)->toThrow(FileOperationException::class);
        expect($this->sessionService->sessionExists($uploadId))->toBeFalse();
        expect(Storage::disk('final_disk')->get('test.txt'))->toBe('existing content');
    });

    it('updates status monotonically during concurrent chunk uploads', function (): void {
        // GIVEN
        $uploadId = UploadId::generate();
        $this->sessionService->createSession($uploadId, new UploadMetadata($uploadId, 'multi.txt', 4, 400, CarbonImmutable::now()));

        $chunks = [
            2 => UploadedFile::fake()->createWithContent('chunk2.part', 'chunk 2'),
            0 => UploadedFile::fake()->createWithContent('chunk0.part', 'chunk 0'),
            3 => UploadedFile::fake()->createWithContent('chunk3.part', 'chunk 3'),
            1 => UploadedFile::fake()->createWithContent('chunk1.part', 'chunk 1'),
        ];

        // WHEN
        foreach ($chunks as $index => $file) {
            $this->sessionService->storeChunk($uploadId, $index, $file);
        }

        // THEN
        $status = $this->sessionService->readStatus($uploadId);
        expect($status)->not->toBeNull();
        expect($status->status)->toBe(UploadStatus::WAITING);
        expect($status->progress)->toBe(100);
    });

    it('does not overwrite non-waiting status during late chunk progress update', function (): void {
        // GIVEN
        $uploadId = UploadId::generate();
        $this->sessionService->createSession($uploadId, new UploadMetadata($uploadId, 'test.txt', 2, 200, CarbonImmutable::now()));
        $this->sessionService->writeStatus($uploadId, UploadStatus::ASSEMBLING, 50);

        // WHEN
        $this->sessionService->updateChunkProgress($uploadId);

        // THEN
        $status = $this->sessionService->readStatus($uploadId);
        expect($status)->not->toBeNull();
        expect($status->status)->toBe(UploadStatus::ASSEMBLING);
        expect($status->progress)->toBe(50);
    });
})->covers(UploadSessionService::class);
