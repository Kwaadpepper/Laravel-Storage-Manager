<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Exception\IllegalDomainStateException;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeDirectory;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeFile;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Tree\PathTreeLevel;

describe('PathTreeLevel', function (): void {
    it('stores a list of PathTreeDirectory instances', function (): void {
        // Given
        $directories = [
            new PathTreeDirectory(new Path('/images'), false),
            new PathTreeDirectory(new Path('/documents'), true),
        ];
        $files = [
            new PathTreeFile(new Path('/documents/report.pdf'), 1024, 'pdf'),
        ];

        // When
        $level = new PathTreeLevel($directories, $files);

        // Then
        expect($level->directories)->toBe($directories);
    });

    it('accepts an empty directories list', function (): void {
        // Given
        $directories = [];
        $files       = [];

        // When
        $level = new PathTreeLevel($directories, $files);

        // Then
        expect($level->directories)->toBeEmpty();
    });

    it('throws a DomainException when the list contains a non-PathTreeDirectory item', function (): void {
        // Given
        $invalidList = [new PathTreeDirectory(new Path('/valid'), false), 'not-a-directory'];
        $files       = [];

        // When / Then
        expect(fn () => new PathTreeLevel($invalidList, $files))
            ->toThrow(IllegalDomainStateException::class, 'All items in directories must be instances of PathTreeDirectory.');
    });
});
