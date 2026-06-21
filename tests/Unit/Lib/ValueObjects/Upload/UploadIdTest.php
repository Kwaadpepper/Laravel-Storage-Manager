<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadId;
use Ramsey\Uuid\Uuid;

describe('UploadId', function (): void {
    it('generates a valid uuid v7', function (): void {
        // Given
        // No specific setup needed

        // When
        $uploadId = UploadId::generate();

        // Then
        expect($uploadId->value)->toBeString()
            ;
        expect(Str::isUuid($uploadId->value))->toBeTrue()
            ;
        expect(Uuid::fromString($uploadId->value)->getVersion())->toBe(7);
    });

    it('accepts a valid uuid', function (): void {
        // Given
        $validUuid = UploadId::generate()->value;

        // When
        $uploadId = new UploadId($validUuid);

        // Then
        expect($uploadId->value)->toBe($validUuid)
            ;
        expect((string) $uploadId)->toBe($validUuid);
    });

    it('throws an exception for invalid uuid', function (): void {
        // Given
        $invalidUuid = 'invalid-uuid-string';

        // When
        $action = fn () => new UploadId($invalidUuid);

        // Then
        expect($action)->toThrow(Kwaadpepper\LaravelStorageManager\Exception\IllegalDomainStateException::class, 'UploadId must be a valid UUID.');
    });
});
