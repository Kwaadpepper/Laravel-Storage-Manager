<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Enum\UploadStatus;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadSessionStatus;

describe('UploadSessionStatus', function (): void {
    it('creates a valid status object', function (): void {
        // GIVEN
        $statusEnum = UploadStatus::TRANSFERRING;
        $progress   = 50;

        // WHEN
        $status = new UploadSessionStatus($statusEnum, $progress);

        // THEN
        expect($status->status)->toBe($statusEnum);
        expect($status->progress)->toBe($progress);
    });

    it('throws exception if progress is less than 0', function (): void {
        // GIVEN
        $statusEnum = UploadStatus::WAITING;
        $progress   = -1;

        // WHEN
        $action = fn () => new UploadSessionStatus($statusEnum, $progress);

        // THEN
        expect($action)->toThrow(\InvalidArgumentException::class, 'progress must be between 0 and 100.');
    });

    it('throws exception if progress is greater than 100', function (): void {
        // GIVEN
        $statusEnum = UploadStatus::WAITING;
        $progress   = 101;

        // WHEN
        $action = fn () => new UploadSessionStatus($statusEnum, $progress);

        // THEN
        expect($action)->toThrow(\InvalidArgumentException::class, 'progress must be between 0 and 100.');
    });

    it('can be serialized and deserialized to array', function (): void {
        // GIVEN
        $status = new UploadSessionStatus(UploadStatus::ASSEMBLING, 10);

        // WHEN
        $array        = $status->toArray();
        $deserialized = UploadSessionStatus::fromArray($array);

        // THEN
        expect($array)->toHaveKey('status', 'assembling')
            ->toHaveKey('progress', 10);

        expect($deserialized->status)->toBe(UploadStatus::ASSEMBLING);
        expect($deserialized->progress)->toBe(10);
    });
});
