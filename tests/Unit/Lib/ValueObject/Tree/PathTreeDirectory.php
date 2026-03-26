<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeDirectory;

describe('PathTreeDirectory', function (): void {
    it('stores path and hasSubDirectories when path starts with a slash', function (): void {
        // Given
        $path              = '/documents/reports';
        $hasSubDirectories = true;

        // When
        $dir = new PathTreeDirectory($path, $hasSubDirectories);

        // Then
        expect($dir->path)->toBe($path)
            ->and($dir->hasSubDirectories)->toBeTrue();
    });

    it('accepts a path with no sub-directories', function (): void {
        // Given
        $path = '/images';

        // When
        $dir = new PathTreeDirectory($path, false);

        // Then
        expect($dir->hasSubDirectories)->toBeFalse();
    });

    it('throws a DomainException when path does not start with a slash', function (): void {
        // Given
        $invalidPath = 'no-leading-slash';

        // When / Then
        expect(fn () => new PathTreeDirectory($invalidPath, false))
            ->toThrow(\DomainException::class, "Path must start with a '/'.");
    });

    it('throws a DomainException when path is empty', function (): void {
        // Given
        $emptyPath = '';

        // When / Then
        expect(fn () => new PathTreeDirectory($emptyPath, false))
            ->toThrow(\DomainException::class, "Path must start with a '/'.");
    });
});
