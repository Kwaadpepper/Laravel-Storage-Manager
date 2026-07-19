<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Kwaadpepper\LaravelStorageManager\Exception\IllegalDomainStateException;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadId;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadMetadata;

describe('UploadMetadata', function (): void {
    it('creates a valid upload metadata object', function (): void {
        // Given
        $uploadId    = UploadId::generate();
        $fileName    = 'test.txt';
        $totalChunks = 2;
        $totalSize   = 1024;
        $createdAt   = CarbonImmutable::now();

        // When
        $metadata = new UploadMetadata($uploadId, $fileName, $totalChunks, $totalSize, $createdAt);

        // Then
        expect($metadata->totalSize)->toBe($totalSize);
        expect($metadata->totalChunks)->toBe($totalChunks);
        expect($metadata->fileName)->toBe($fileName);
        expect($metadata->uploadId->value)->toBe($uploadId->value);
        expect($metadata->createdAt->toIso8601String())->toBe($createdAt->toIso8601String());
    });

    it('throws exception when fileName is empty', function (): void {
        // Given
        $uploadId    = UploadId::generate();
        $fileName    = '   ';
        $totalChunks = 2;
        $totalSize   = 1024;
        $createdAt   = CarbonImmutable::now();

        // When
        $action = fn () => new UploadMetadata($uploadId, $fileName, $totalChunks, $totalSize, $createdAt);

        // Then
        expect($action)->toThrow(IllegalDomainStateException::class, 'fileName cannot be empty.');
    });

    it('throws exception when totalChunks is less than 1', function (): void {
        // Given
        $uploadId    = UploadId::generate();
        $fileName    = 'test.txt';
        $totalChunks = 0;
        $totalSize   = 1024;
        $createdAt   = CarbonImmutable::now();

        // When
        $action = fn () => new UploadMetadata($uploadId, $fileName, $totalChunks, $totalSize, $createdAt);

        // Then
        expect($action)->toThrow(IllegalDomainStateException::class, 'totalChunks must be greater than 0.');
    });

    it('throws exception when totalSize is negative or zero', function (): void {
        // Given
        $uploadId    = UploadId::generate();
        $fileName    = 'test.txt';
        $totalChunks = 2;
        $totalSize   = 0;
        $createdAt   = CarbonImmutable::now();

        // When
        $action = fn () => new UploadMetadata($uploadId, $fileName, $totalChunks, $totalSize, $createdAt);

        // Then
        expect($action)->toThrow(IllegalDomainStateException::class, 'totalSize must be greater than 0.');
    });

    it('can be serialized and deserialized to array', function (): void {
        // Given
        $uploadId  = UploadId::generate();
        $createdAt = CarbonImmutable::now();
        $metadata  = new UploadMetadata($uploadId, 'test.txt', 2, 1024, $createdAt);

        // When
        $array        = $metadata->toArray();
        $deserialized = UploadMetadata::fromArray($array);

        // Then
        expect($array)->toHaveKey('totalSize', 1024)
            ->toHaveKey('totalChunks', 2)
            ->toHaveKey('fileName', 'test.txt')
            ->toHaveKey('uploadId', $uploadId->value)
            ->toHaveKey('created_at', $createdAt->toIso8601String());

        expect($deserialized->totalSize)->toBe(1024);
        expect($deserialized->fileName)->toBe('test.txt');
        expect($deserialized->uploadId->value)->toBe($uploadId->value);
        expect($deserialized->createdAt->toIso8601String())->toBe($createdAt->toIso8601String());
    });

    it('gracefully handles missing uploadId during deserialization', function (): void {
        // Given
        $legacyArray = [
            'totalSize'   => 500,
            'totalChunks' => 1,
            'fileName'    => 'legacy.txt',
            'created_at'  => CarbonImmutable::now()->toIso8601String(),
        ];

        // When
        $deserialized = UploadMetadata::fromArray($legacyArray);

        // Then
        expect($deserialized->totalSize)->toBe(500);
        expect($deserialized->fileName)->toBe('legacy.txt');
        expect($deserialized->uploadId)->toBeInstanceOf(UploadId::class); // It should generate a new one
    });
});
