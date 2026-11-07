<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Enum\UploadStatus;
use Kwaadpepper\LaravelStorageManager\Lib\Upload\UploadSessionService;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadId;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadSessionStatus;
use Kwaadpepper\LaravelStorageManager\Query\Upload\GetUploadStatusInput;
use Kwaadpepper\LaravelStorageManager\Query\Upload\GetUploadStatusQuery;

describe('GetUploadStatusQuery', function (): void {
    it('returns status from UploadSessionService', function (): void {
        // GIVEN
        $sessionService = \Mockery::mock(UploadSessionService::class);
        $uploadId       = UploadId::generate();
        $status         = new UploadSessionStatus(status: UploadStatus::TRANSFERRING, progress: 45);

        $sessionService->shouldReceive('readStatus')->with($uploadId)->once()->andReturn($status);

        $query = new GetUploadStatusQuery($sessionService);

        // WHEN
        $result = $query(new GetUploadStatusInput($uploadId));

        // THEN
        expect($result->status)->toBe(UploadStatus::TRANSFERRING);
        expect($result->progress)->toBe(45);
    });

    it('returns waiting status when session status is null', function (): void {
        // GIVEN
        $sessionService = \Mockery::mock(UploadSessionService::class);
        $uploadId       = UploadId::generate();

        $sessionService->shouldReceive('readStatus')->with($uploadId)->once()->andReturnNull();

        $query = new GetUploadStatusQuery($sessionService);

        // WHEN
        $result = $query(new GetUploadStatusInput($uploadId));

        // THEN
        expect($result->status)->toBe(UploadStatus::WAITING);
        expect($result->progress)->toBe(0);
    });
})->covers(GetUploadStatusQuery::class);
