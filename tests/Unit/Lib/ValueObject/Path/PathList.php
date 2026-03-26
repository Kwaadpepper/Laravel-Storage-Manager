<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\PathList;

describe('PathList', function (): void {
    it('stores files and directories arrays', function (): void {
        // Given
        $files       = [new Path('/uploads/photo.jpg')];
        $directories = [new Path('/uploads')];

        // When
        $list = new PathList($files, $directories);

        // Then
        expect($list->files)->toBe($files)
            ->and($list->directories)->toBe($directories);
    });

    it('accepts empty files and directories arrays', function (): void {
        // Given / When
        $list = new PathList([], []);

        // Then
        expect($list->files)->toBeEmpty()
            ->and($list->directories)->toBeEmpty();
    });

    it('throws a DomainException when files contains a non-Path item', function (): void {
        // Given
        $invalidFiles = [new Path('/valid.txt'), 'not-a-path'];

        // When / Then
        expect(fn () => new PathList($invalidFiles, []))
            ->toThrow(\DomainException::class, 'All items in files must be instances of Path.');
    });

    it('throws a DomainException when directories contains a non-Path item', function (): void {
        // Given
        $invalidDirs = [new Path('/valid'), 42];

        // When / Then
        expect(fn () => new PathList([], $invalidDirs))
            ->toThrow(\DomainException::class, 'All items in directories must be instances of Path.');
    });
});
