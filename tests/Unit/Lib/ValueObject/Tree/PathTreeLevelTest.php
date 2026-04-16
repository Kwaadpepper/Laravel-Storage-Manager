<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeDirectory;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeLevel;

describe('PathTreeLevel', function (): void {
    it('stores a list of PathTreeDirectory instances', function (): void {
        // Given
        $directories = [
            new PathTreeDirectory('/images', false),
            new PathTreeDirectory('/documents', true),
        ];

        // When
        $level = new PathTreeLevel($directories);

        // Then
        expect($level->directories)->toBe($directories);
    });

    it('accepts an empty directories list', function (): void {
        // Given
        $directories = [];

        // When
        $level = new PathTreeLevel($directories);

        // Then
        expect($level->directories)->toBeEmpty();
    });

    it('throws a DomainException when the list contains a non-PathTreeDirectory item', function (): void {
        // Given
        $invalidList = [new PathTreeDirectory('/valid', false), 'not-a-directory'];

        // When / Then
        expect(fn () => new PathTreeLevel($invalidList))
            ->toThrow(\DomainException::class, 'All items in directories must be instances of PathTreeDirectory.');
    });
});
