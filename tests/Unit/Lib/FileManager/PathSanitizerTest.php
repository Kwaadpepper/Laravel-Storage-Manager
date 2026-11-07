<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathSanitizer;

describe('PathSanitizer', function (): void {
    it('replaces forbidden characters with spaces in directory names', function (): void {
        // GIVEN
        $sanitizer = new PathSanitizer();
        $dirtyName = 'my/folder<name>';

        // WHEN
        $result = $sanitizer->sanitizeDirectoryName($dirtyName);

        // THEN
        expect($result)->toBe('my folder name');
    });

    it('keeps valid file names unchanged including parentheses and brackets', function (): void {
        // GIVEN
        $sanitizer  = new PathSanitizer();
        $validName1 = 'photo (1).jpg';
        $validName2 = 'rapport [final].pdf';
        $validName3 = "file+test&notes@work#1=ok's.txt";

        // WHEN
        $result1 = $sanitizer->sanitizeFileName($validName1);
        $result2 = $sanitizer->sanitizeFileName($validName2);
        $result3 = $sanitizer->sanitizeFileName($validName3);

        // THEN
        expect($result1)->toBe('photo (1).jpg');
        expect($result2)->toBe('rapport [final].pdf');
        expect($result3)->toBe("file+test&notes@work#1=ok's.txt");
    });

    it('strips leading dots from file names', function (): void {
        // GIVEN
        $sanitizer  = new PathSanitizer();
        $hiddenFile = '.hidden';

        // WHEN
        $result = $sanitizer->sanitizeFileName($hiddenFile);

        // THEN
        expect($result)->toBe('hidden');
    });

    it('validates names correctly with isValidName', function (): void {
        // GIVEN
        $sanitizer = new PathSanitizer();

        // WHEN
        $validPhoto       = $sanitizer->isValidName('photo (1).jpg');
        $validReport      = $sanitizer->isValidName('rapport [final].pdf');
        $validSpecial     = $sanitizer->isValidName("file+test&notes@work#1=ok's.txt");
        $invalidNull      = $sanitizer->isValidName(null);
        $invalidInt       = $sanitizer->isValidName(123);
        $invalidArray     = $sanitizer->isValidName(['foo']);
        $invalidEmpty     = $sanitizer->isValidName('');
        $invalidSpaces    = $sanitizer->isValidName('   ');
        $invalidDot       = $sanitizer->isValidName('.');
        $invalidDotDot    = $sanitizer->isValidName('..');
        $invalidLong      = $sanitizer->isValidName(str_repeat('a', 256));
        $invalidSlash     = $sanitizer->isValidName('foo/bar');
        $invalidBackslash = $sanitizer->isValidName('foo\\bar');
        $invalidColon     = $sanitizer->isValidName('foo:bar');
        $invalidStar      = $sanitizer->isValidName('foo*bar');
        $invalidNullByte  = $sanitizer->isValidName("foo\x00bar");

        // THEN
        expect($validPhoto)->toBeTrue();
        expect($validReport)->toBeTrue();
        expect($validSpecial)->toBeTrue();
        expect($invalidNull)->toBeFalse();
        expect($invalidInt)->toBeFalse();
        expect($invalidArray)->toBeFalse();
        expect($invalidEmpty)->toBeFalse();
        expect($invalidSpaces)->toBeFalse();
        expect($invalidDot)->toBeFalse();
        expect($invalidDotDot)->toBeFalse();
        expect($invalidLong)->toBeFalse();
        expect($invalidSlash)->toBeFalse();
        expect($invalidBackslash)->toBeFalse();
        expect($invalidColon)->toBeFalse();
        expect($invalidStar)->toBeFalse();
        expect($invalidNullByte)->toBeFalse();
    });

    it('validates file names with isValidFileName', function (): void {
        // GIVEN
        $sanitizer = new PathSanitizer();

        // WHEN
        $valid   = $sanitizer->isValidFileName('photo (1).jpg');
        $invalid = $sanitizer->isValidFileName('invalid/file.jpg');

        // THEN
        expect($valid)->toBeTrue();
        expect($invalid)->toBeFalse();
    });

    it('validates directory names with isValidDirectoryName', function (): void {
        // GIVEN
        $sanitizer = new PathSanitizer();

        // WHEN
        $valid   = $sanitizer->isValidDirectoryName('My Folder (2)');
        $invalid = $sanitizer->isValidDirectoryName('invalid:dir');

        // THEN
        expect($valid)->toBeTrue();
        expect($invalid)->toBeFalse();
    });
})->covers(PathSanitizer::class);
