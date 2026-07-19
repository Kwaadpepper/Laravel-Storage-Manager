<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Enum\UploadStatus;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadSessionStatus;

describe('UploadSessionStatus', function (): void {
    it('creates a valid status object', function (): void {
        // Given
        $statusEnum = UploadStatus::TRANSFERRING;
        $progress   = 50;

        // When
        $status = new UploadSessionStatus($statusEnum, $progress);

        // Then
        expect($status->status)->toBe($statusEnum);
        expect($status->progress)->toBe($progress);
    });

    it('throws exception if progress is less than 0', function (): void {
        // Given
        $statusEnum = UploadStatus::WAITING;
        $progress   = -1;

        // When
        $action = fn () => new UploadSessionStatus($statusEnum, $progress);

        // Then
        expect($action)->toThrow(\InvalidArgumentException::class, 'progress must be between 0 and 100.');
    });

    it('throws exception if progress is greater than 100', function (): void {
        // Given
        $statusEnum = UploadStatus::WAITING;
        $progress   = 101;

        // When
        $action = fn () => new UploadSessionStatus($statusEnum, $progress);

        // Then
        expect($action)->toThrow(\InvalidArgumentException::class, 'progress must be between 0 and 100.');
    });

    it('can be serialized and deserialized to array', function (): void {
        // Given
        $status = new UploadSessionStatus(UploadStatus::ASSEMBLING, 10);

        // When
        $array        = $status->toArray();
        $deserialized = UploadSessionStatus::fromArray($array);

        // Then
        expect($array)->toHaveKey('status', 'assembling')
            ->toHaveKey('progress', 10);

        expect($deserialized->status)->toBe(UploadStatus::ASSEMBLING);
        expect($deserialized->progress)->toBe(10);
    });
});
