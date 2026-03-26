<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\DirectoryPathProperties;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\FilePathProperties;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathPropertyFactory;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathVisibility;

describe('PathPropertyFactory', function (): void {
    describe('fromArray() — type file', function (): void {
        it('returns a FilePathProperties for type file with all explicit fields', function (): void {
            // Given
            $metadata = [
                'type'       => 'file',
                'path'       => '/uploads/photo.jpg',
                'basename'   => 'photo.jpg',
                'dirname'    => '/uploads',
                'extension'  => 'jpg',
                'filename'   => 'photo',
                'size'       => 2048,
                'timestamp'  => 0,
                'visibility' => 'public',
            ];

            // When
            $result = PathPropertyFactory::fromArray($metadata);

            // Then
            expect($result)->toBeInstanceOf(FilePathProperties::class)
                ->and($result->path->value)->toBe('/uploads/photo.jpg')
                ->and($result->basename)->toBe('photo.jpg')
                ->and($result->dirname)->toBe('/uploads')
                ->and($result->extension)->toBe('jpg')
                ->and($result->filename)->toBe('photo')
                ->and($result->size)->toBe(2048)
                ->and($result->visibility)->toBe(PathVisibility::PUBLIC);
        });

        it('falls back to defaults when optional file fields are absent', function (): void {
            // Given
            $metadata = [
                'type'      => 'file',
                'path'      => '/uploads/photo.jpg',
                'timestamp' => 0,
            ];

            // When
            $result = PathPropertyFactory::fromArray($metadata);

            // Then
            expect($result)->toBeInstanceOf(FilePathProperties::class)
                ->and($result->extension)->toBe('')
                ->and($result->filename)->toBe('')
                ->and($result->size)->toBe(0)
                ->and($result->visibility)->toBe(PathVisibility::PRIVATE);
        });

        it('parses a Unix timestamp integer', function (): void {
            // Given
            $timestamp = 1_700_000_000;
            $metadata  = ['type' => 'file', 'path' => '/file.txt', 'timestamp' => $timestamp];

            // When
            $result = PathPropertyFactory::fromArray($metadata);

            // Then
            expect($result->timestamp->getTimestamp())->toBe($timestamp);
        });

        it('parses a datetime string as timestamp', function (): void {
            // Given
            $metadata = ['type' => 'file', 'path' => '/file.txt', 'timestamp' => '2024-01-15 10:00:00'];

            // When
            $result = PathPropertyFactory::fromArray($metadata);

            // Then
            expect($result->timestamp)->toBeInstanceOf(\DateTimeImmutable::class);
        });
    });

    describe('fromArray() — type dir', function (): void {
        it('returns a DirectoryPathProperties for type dir with all explicit fields', function (): void {
            // Given
            $metadata = [
                'type'       => 'dir',
                'path'       => '/documents',
                'basename'   => 'documents',
                'dirname'    => '/',
                'timestamp'  => 0,
                'visibility' => 'private',
            ];

            // When
            $result = PathPropertyFactory::fromArray($metadata);

            // Then
            expect($result)->toBeInstanceOf(DirectoryPathProperties::class)
                ->and($result->path->value)->toBe('/documents')
                ->and($result->basename)->toBe('documents')
                ->and($result->dirname)->toBe('/')
                ->and($result->visibility)->toBe(PathVisibility::PRIVATE);
        });

        it('falls back to defaults when optional directory fields are absent', function (): void {
            // Given
            $metadata = ['type' => 'dir', 'path' => '/documents', 'timestamp' => 0];

            // When
            $result = PathPropertyFactory::fromArray($metadata);

            // Then
            expect($result)->toBeInstanceOf(DirectoryPathProperties::class)
                ->and($result->visibility)->toBe(PathVisibility::PRIVATE);
        });
    });

    describe('fromArray() — error cases', function (): void {
        it('throws InvalidArgumentException when type field is missing', function (): void {
            // Given
            $metadata = ['path' => '/file.txt'];

            // When / Then
            expect(fn () => PathPropertyFactory::fromArray($metadata))
                ->toThrow(\InvalidArgumentException::class, "Le champ 'type' est obligatoire.");
        });

        it('throws InvalidArgumentException for an unknown type', function (): void {
            // Given
            $metadata = ['type' => 'symlink', 'path' => '/link', 'timestamp' => 0];

            // When / Then
            expect(fn () => PathPropertyFactory::fromArray($metadata))
                ->toThrow(\InvalidArgumentException::class, 'Type de chemin invalide : symlink');
        });

        it('throws InvalidArgumentException for an invalid visibility', function (): void {
            // Given
            $metadata = ['type' => 'file', 'path' => '/file.txt', 'timestamp' => 0, 'visibility' => 'world'];

            // When / Then
            expect(fn () => PathPropertyFactory::fromArray($metadata))
                ->toThrow(\InvalidArgumentException::class, 'Visibilité invalide : world');
        });

        it('throws InvalidArgumentException for an unparseable timestamp string', function (): void {
            // Given
            $metadata = ['type' => 'file', 'path' => '/file.txt', 'timestamp' => 'not-a-date-xyz!!!'];

            // When / Then
            expect(fn () => PathPropertyFactory::fromArray($metadata))
                ->toThrow(\InvalidArgumentException::class);
        });
    });
});
