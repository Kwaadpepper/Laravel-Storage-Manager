<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathSanitizer;

describe('PathSanitizer', function (): void {
    it('replaces forbidden characters with spaces in directory names', function (): void {
        // Given
        $sanitizer = new PathSanitizer();
        $dirtyName = 'my/folder<name>';

        // When
        $result = $sanitizer->sanitizeDirectoryName($dirtyName);

        // Then
        expect($result)->toBe('my folder name');
    });

    it('keeps valid file names unchanged', function (): void {
        // Given
        $sanitizer = new PathSanitizer();
        $validName = 'hello world.txt';

        // When
        $result = $sanitizer->sanitizeFileName($validName);

        // Then
        expect($result)->toBe('hello world.txt');
    });

    it('strips leading dots from file names', function (): void {
        // Given
        $sanitizer  = new PathSanitizer();
        $hiddenFile = '.hidden';

        // When
        $result = $sanitizer->sanitizeFileName($hiddenFile);

        // Then
        expect($result)->toBe('hidden');
    });
});
