<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;

describe('Path', function (): void {
    it('stores the value as a readonly property', function (): void {
        // Given
        $value = '/uploads/photo.jpg';

        // When
        $path = new Path($value);

        // Then
        expect($path->value)->toBe($value);
    });

    it('casts to string via __toString', function (): void {
        // Given
        $value = '/uploads/photo.jpg';
        $path  = new Path($value);

        // When
        $result = (string) $path;

        // Then
        expect($result)->toBe($value);
    });

    it('throws a DomainException when path does not start with a slash', function (): void {
        // Given
        $invalidPath = 'no-leading-slash';

        // When / Then
        expect(fn () => new Path($invalidPath))
            ->toThrow(\DomainException::class, "Path must start with a '/'.");
    });

    it('throws a DomainException when path is empty', function (): void {
        // Given
        $emptyPath = '';

        // When / Then
        expect(fn () => new Path($emptyPath))
            ->toThrow(\DomainException::class, "Path must start with a '/'.");
    });

    it('appends a segment without a leading slash to a base path', function (): void {
        // Given
        $basePath = new Path('/uploads');
        $segment  = 'photo.jpg';

        // When
        $result = Path::appendTo($basePath, $segment);

        // Then
        expect((string) $result)->toBe('/uploads/photo.jpg');
    });

    it('appends a segment with a leading slash by normalising it', function (): void {
        // Given
        $basePath = new Path('/uploads');
        $segment  = '/photo.jpg';

        // When
        $result = Path::appendTo($basePath, $segment);

        // Then
        expect((string) $result)->toBe('/uploads/photo.jpg');
    });

    it('strips the trailing slash of the base path before appending', function (): void {
        // Given
        $basePath = new Path('/uploads/');
        $segment  = 'photo.jpg';

        // When
        $result = Path::appendTo($basePath, $segment);

        // Then
        expect((string) $result)->toBe('/uploads/photo.jpg');
    });
});
