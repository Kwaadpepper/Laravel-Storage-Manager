<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Storage;

describe('Storage', function (): void {
    it('stores name and disk as readonly properties', function (): void {
        // Given
        $name = 'my-storage';
        $disk = 'local';

        // When
        $storage = new Storage($name, $disk);

        // Then
        expect($storage->name)->toBe($name)
            ->and($storage->disk)->toBe($disk);
    });

    it('throws a DomainException when name is empty', function (): void {
        // Given
        $emptyName = '';

        // When / Then
        expect(fn () => new Storage($emptyName, 'local'))
            ->toThrow(\DomainException::class, 'Storage name cannot be empty.');
    });

    it('throws a DomainException when disk is empty', function (): void {
        // Given
        $emptyDisk = '';

        // When / Then
        expect(fn () => new Storage('my-storage', $emptyDisk))
            ->toThrow(\DomainException::class, 'Storage disk cannot be empty.');
    });
});
