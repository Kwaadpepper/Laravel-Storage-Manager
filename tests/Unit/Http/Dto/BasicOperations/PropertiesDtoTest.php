<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Http\Dto\BasicOperations\PropertiesDto;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\DirectoryPathProperties;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\FilePathProperties;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathVisibility;

describe('PropertiesDto', function (): void {
    it('creates a PropertiesDto from FilePathProperties', function (): void {
        // GIVEN
        $timestamp = new \DateTimeImmutable('2026-01-01T12:00:00Z');
        $props     = new FilePathProperties(
            path: new Path('/documents/test.txt'),
            basename: 'test.txt',
            dirname: '/documents',
            extension: 'txt',
            filename: 'test',
            size: 123,
            timestamp: $timestamp,
            visibility: PathVisibility::PUBLIC,
            mimeType: 'text/plain',
            publicUrl: 'http://example.com/test.txt',
            checksum: 'abc123md5',
            dimensions: null,
            exif: null,
            rawExif: null,
        );

        // WHEN
        $dto = PropertiesDto::fromPathProperties($props);

        // THEN
        expect($dto->path)->toBe('/documents/test.txt');
        expect($dto->basename)->toBe('test.txt');
        expect($dto->dirname)->toBe('/documents');
        expect($dto->extension)->toBe('txt');
        expect($dto->size)->toBe(123);
        expect($dto->isFile)->toBeTrue();
        expect($dto->isDirectory)->toBeFalse();
        expect($dto->mimeType)->toBe('text/plain');
        expect($dto->checksum)->toBe('abc123md5');

        $serialized = $dto->jsonSerialize();
        expect($serialized['isFile'])->toBeTrue();
        expect(array_key_exists('isDirectory', $serialized))->toBeFalse();
    });

    it('creates a PropertiesDto from DirectoryPathProperties', function (): void {
        // GIVEN
        $timestamp = new \DateTimeImmutable('2026-01-01T12:00:00Z');
        $props     = new DirectoryPathProperties(
            path: new Path('/documents'),
            basename: 'documents',
            dirname: '/',
            timestamp: $timestamp,
            visibility: PathVisibility::PRIVATE,
        );

        // WHEN
        $dto = PropertiesDto::fromPathProperties($props);

        // THEN
        expect($dto->path)->toBe('/documents');
        expect($dto->basename)->toBe('documents');
        expect($dto->dirname)->toBe('/');
        expect($dto->isFile)->toBeFalse();
        expect($dto->isDirectory)->toBeTrue();
        expect($dto->extension)->toBeNull();
        expect($dto->size)->toBeNull();

        $serialized = $dto->jsonSerialize();
        expect($serialized['isDirectory'])->toBeTrue();
        expect(array_key_exists('isFile', $serialized))->toBeFalse();
        expect(array_key_exists('extension', $serialized))->toBeFalse();
    });
});
