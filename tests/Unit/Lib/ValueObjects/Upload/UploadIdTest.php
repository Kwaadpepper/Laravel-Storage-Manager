<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Kwaadpepper\LaravelStorageManager\Exception\IllegalDomainStateException;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadId;
use Ramsey\Uuid\Uuid;

describe('UploadId', function (): void {
    it('generates a valid uuid v7', function (): void {
        // GIVEN

        // WHEN
        $uploadId = UploadId::generate();

        // THEN
        expect($uploadId->value)->toBeString();
        expect(Str::isUuid($uploadId->value))->toBeTrue();
        expect(Uuid::fromString($uploadId->value)->getVersion())->toBe(7);
    });

    it('accepts a valid uuid', function (): void {
        // GIVEN
        $validUuid = UploadId::generate()->value;

        // WHEN
        $uploadId = new UploadId($validUuid);

        // THEN
        expect($uploadId->value)->toBe($validUuid);
        expect((string) $uploadId)->toBe($validUuid);
    });

    it('throws an exception for invalid uuid', function (): void {
        // GIVEN
        $invalidUuid = 'invalid-uuid-string';

        // WHEN
        $action = fn () => new UploadId($invalidUuid);

        // THEN
        expect($action)->toThrow(IllegalDomainStateException::class, 'UploadId must be a valid UUID.');
    });
});
