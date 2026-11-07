<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\DirectoryPathProperties;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\FilePathProperties;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathPropertyFactory;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathVisibility;

describe('PathPropertyFactory', function (): void {
    describe('fromArray() - type file', function (): void {
        it('returns a FilePathProperties for type file with all explicit fields', function (): void {
            // GIVEN
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

            // WHEN
            $result = PathPropertyFactory::fromArray($metadata);

            // THEN
            expect($result)->toBeInstanceOf(FilePathProperties::class);
            expect($result->path->value)->toBe('/uploads/photo.jpg');
            expect($result->basename)->toBe('photo.jpg');
            expect($result->dirname)->toBe('/uploads');
            expect($result->extension)->toBe('jpg');
            expect($result->filename)->toBe('photo');
            expect($result->size)->toBe(2048);
            expect($result->visibility)->toBe(PathVisibility::PUBLIC);
        });

        it('falls back to defaults when optional file fields are absent', function (): void {
            // GIVEN
            $metadata = [
                'type'      => 'file',
                'path'      => '/uploads/photo.jpg',
                'timestamp' => 0,
            ];

            // WHEN
            $result = PathPropertyFactory::fromArray($metadata);

            // THEN
            expect($result)->toBeInstanceOf(FilePathProperties::class);
            expect($result->extension)->toBe('');
            expect($result->filename)->toBe('');
            expect($result->size)->toBe(0);
            expect($result->visibility)->toBeNull();
        });

        it('parses a Unix timestamp integer', function (): void {
            // GIVEN
            $timestamp = 1_700_000_000;
            $metadata  = ['type' => 'file', 'path' => '/file.txt', 'timestamp' => $timestamp];

            // WHEN
            $result = PathPropertyFactory::fromArray($metadata);

            // THEN
            expect($result->timestamp->getTimestamp())->toBe($timestamp);
        });

        it('parses a datetime string as timestamp', function (): void {
            // GIVEN
            $metadata = ['type' => 'file', 'path' => '/file.txt', 'timestamp' => '2024-01-15 10:00:00'];

            // WHEN
            $result = PathPropertyFactory::fromArray($metadata);

            // THEN
            expect($result->timestamp)->toBeInstanceOf(\DateTimeImmutable::class);
        });
    });

    describe('fromArray() - type dir', function (): void {
        it('returns a DirectoryPathProperties for type dir with all explicit fields', function (): void {
            // GIVEN
            $metadata = [
                'type'       => 'dir',
                'path'       => '/documents',
                'basename'   => 'documents',
                'dirname'    => '/',
                'timestamp'  => 0,
                'visibility' => 'private',
            ];

            // WHEN
            $result = PathPropertyFactory::fromArray($metadata);

            // THEN
            expect($result)->toBeInstanceOf(DirectoryPathProperties::class);
            expect($result->path->value)->toBe('/documents');
            expect($result->basename)->toBe('documents');
            expect($result->dirname)->toBe('/');
            expect($result->visibility)->toBe(PathVisibility::PRIVATE);
        });

        it('falls back to defaults when optional directory fields are absent', function (): void {
            // GIVEN
            $metadata = ['type' => 'dir', 'path' => '/documents', 'timestamp' => 0];

            // WHEN
            $result = PathPropertyFactory::fromArray($metadata);

            // THEN
            expect($result)->toBeInstanceOf(DirectoryPathProperties::class);
            expect($result->visibility)->toBeNull();
        });
    });

    describe('fromArray() - error cases', function (): void {
        it('throws InvalidArgumentException when type field is missing', function (): void {
            // GIVEN
            $metadata = ['path' => '/file.txt'];

            // WHEN
            $action = fn () => PathPropertyFactory::fromArray($metadata);

            // THEN
            expect($action)
                ->toThrow(\InvalidArgumentException::class, "Le champ 'type' est obligatoire.");
        });

        it('throws InvalidArgumentException for an unknown type', function (): void {
            // GIVEN
            $metadata = ['type' => 'symlink', 'path' => '/link', 'timestamp' => 0];

            // WHEN
            $action = fn () => PathPropertyFactory::fromArray($metadata);

            // THEN
            expect($action)
                ->toThrow(\InvalidArgumentException::class, 'Type de chemin invalide : symlink');
        });

        it('throws InvalidArgumentException for an invalid visibility', function (): void {
            // GIVEN
            $metadata = ['type' => 'file', 'path' => '/file.txt', 'timestamp' => 0, 'visibility' => 'world'];

            // WHEN
            $action = fn () => PathPropertyFactory::fromArray($metadata);

            // THEN
            expect($action)
                ->toThrow(\InvalidArgumentException::class, 'Visibilité invalide : world');
        });

        it('throws InvalidArgumentException for an unparseable timestamp string', function (): void {
            // GIVEN
            $metadata = ['type' => 'file', 'path' => '/file.txt', 'timestamp' => 'not-a-date-xyz!!!'];

            // WHEN
            $action = fn () => PathPropertyFactory::fromArray($metadata);

            // THEN
            expect($action)
                ->toThrow(\InvalidArgumentException::class);
        });
    });
});
