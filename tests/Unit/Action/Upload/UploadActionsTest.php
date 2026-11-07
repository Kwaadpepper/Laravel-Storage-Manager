<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Kwaadpepper\LaravelStorageManager\Action\Upload\CompleteUploadAction;
use Kwaadpepper\LaravelStorageManager\Action\Upload\CompleteUploadInput;
use Kwaadpepper\LaravelStorageManager\Action\Upload\InitUploadAction;
use Kwaadpepper\LaravelStorageManager\Action\Upload\InitUploadInput;
use Kwaadpepper\LaravelStorageManager\Action\Upload\UploadChunkAction;
use Kwaadpepper\LaravelStorageManager\Action\Upload\UploadChunkInput;
use Kwaadpepper\LaravelStorageManager\Lib\Upload\UploadSessionService;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadId;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadMetadata;

describe('UploadActions', function (): void {
    it('creates upload session via InitUploadAction', function (): void {
        // GIVEN
        $sessionService = \Mockery::mock(UploadSessionService::class);
        $uploadId       = UploadId::generate();
        $metadata       = new UploadMetadata(
            uploadId: $uploadId,
            fileName: 'file.txt',
            totalChunks: 3,
            totalSize: 1024,
            createdAt: CarbonImmutable::now()
        );
        $input = new InitUploadInput($uploadId, $metadata);

        $sessionService->shouldReceive('createSession')->with($uploadId, $metadata)->once();

        $action = new InitUploadAction($sessionService);

        // WHEN
        $action($input);

        // THEN
        expect(true)->toBeTrue();
    });

    it('stores chunk via UploadChunkAction', function (): void {
        // GIVEN
        $sessionService = \Mockery::mock(UploadSessionService::class);
        $uploadId       = UploadId::generate();
        $file           = UploadedFile::fake()->create('chunk.bin', 100);
        $input          = new UploadChunkInput($uploadId, 0, $file);

        $sessionService->shouldReceive('storeChunk')
            ->with($uploadId, 0, $file)
            ->once();

        $action = new UploadChunkAction($sessionService);

        // WHEN
        $action($input);

        // THEN
        expect(true)->toBeTrue();
    });

    it('completes upload via CompleteUploadAction', function (): void {
        // GIVEN
        $sessionService = \Mockery::mock(UploadSessionService::class);
        $uploadId       = UploadId::generate();
        $disk           = new Disk('local', 'local');
        $destinationDir = new Path('/uploads');
        $input          = new CompleteUploadInput(
            uploadId: $uploadId,
            disk: $disk,
            destinationDir: $destinationDir,
            fileName: 'final.txt'
        );

        $sessionService->shouldReceive('completeUpload')
            ->with($uploadId, $disk, $destinationDir, 'final.txt', null, null)
            ->once()
            ->andReturn('final.txt');

        $action = new CompleteUploadAction($sessionService);

        // WHEN
        $result = $action($input);

        // THEN
        expect($result)->toBe('final.txt');
    });
})->covers(InitUploadAction::class, UploadChunkAction::class, CompleteUploadAction::class);
