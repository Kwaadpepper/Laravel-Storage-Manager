<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathNormalizer;

describe('PathNormalizer', function (): void {
    it('should normalize paths correctly', function (): void {
        // GIVEN
        $normalizer = new PathNormalizer();

        // WHEN
        $res1 = $normalizer->normalizePath('  /foo//bar/../baz/ ');
        $res2 = $normalizer->normalizePath('./foo/./bar/');
        $res3 = $normalizer->normalizePath('/foo/../../bar/');
        $res4 = $normalizer->normalizePath('foo//bar///baz');
        $res5 = $normalizer->normalizePath('/foo/./././bar/');
        $res6 = $normalizer->normalizePath('/foo/.././bar/../baz/');
        $res7 = $normalizer->normalizePath('./../..');

        // THEN
        expect($res1)->toBe('/foo/baz');
        expect($res2)->toBe('/foo/bar');
        expect($res3)->toBe('/bar');
        expect($res4)->toBe('/foo/bar/baz');
        expect($res5)->toBe('/foo/bar');
        expect($res6)->toBe('/baz');
        expect($res7)->toBe('/');
    });

    it('validates paths with isValidPath', function (): void {
        // GIVEN
        $normalizer = new PathNormalizer();

        // WHEN
        $validRoot         = $normalizer->isValidPath('/');
        $validFile         = $normalizer->isValidPath('/foo/bar.txt');
        $validDir          = $normalizer->isValidPath('/folder/subfolder');
        $invalidNull       = $normalizer->isValidPath(null);
        $invalidInt        = $normalizer->isValidPath(123);
        $invalidArray      = $normalizer->isValidPath(['/path']);
        $invalidEmpty      = $normalizer->isValidPath('');
        $invalidWhitespace = $normalizer->isValidPath('   ');
        $invalidRelative   = $normalizer->isValidPath('foo/bar');
        $invalidNullByte   = $normalizer->isValidPath("/foo\0/bar");
        $invalidTraversal1 = $normalizer->isValidPath('/foo/../bar');
        $invalidTraversal2 = $normalizer->isValidPath('/..');
        $invalidTraversal3 = $normalizer->isValidPath('/foo/bar/..');

        // THEN
        expect($validRoot)->toBeTrue();
        expect($validFile)->toBeTrue();
        expect($validDir)->toBeTrue();
        expect($invalidNull)->toBeFalse();
        expect($invalidInt)->toBeFalse();
        expect($invalidArray)->toBeFalse();
        expect($invalidEmpty)->toBeFalse();
        expect($invalidWhitespace)->toBeFalse();
        expect($invalidRelative)->toBeFalse();
        expect($invalidNullByte)->toBeFalse();
        expect($invalidTraversal1)->toBeFalse();
        expect($invalidTraversal2)->toBeFalse();
        expect($invalidTraversal3)->toBeFalse();
    });

    it('detects root path with isRootPath', function (): void {
        // GIVEN
        $normalizer = new PathNormalizer();

        // WHEN
        $rootSlash      = $normalizer->isRootPath('/');
        $rootDouble     = $normalizer->isRootPath('//');
        $rootDot        = $normalizer->isRootPath('/.');
        $rootTrim       = $normalizer->isRootPath('  /  ');
        $rootEmpty      = $normalizer->isRootPath('');
        $notRootFolder  = $normalizer->isRootPath('/folder');
        $notRootFile    = $normalizer->isRootPath('/file.txt');
        $notRootNull    = $normalizer->isRootPath(null);
        $notRootInt     = $normalizer->isRootPath(42);

        // THEN
        expect($rootSlash)->toBeTrue();
        expect($rootDouble)->toBeTrue();
        expect($rootDot)->toBeTrue();
        expect($rootTrim)->toBeTrue();
        expect($rootEmpty)->toBeTrue();
        expect($notRootFolder)->toBeFalse();
        expect($notRootFile)->toBeFalse();
        expect($notRootNull)->toBeFalse();
        expect($notRootInt)->toBeFalse();
    });
})->covers(PathNormalizer::class);
