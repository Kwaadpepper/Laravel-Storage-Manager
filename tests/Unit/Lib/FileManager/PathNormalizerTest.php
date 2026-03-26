<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathNormalizer;

describe('PathNormalizer', function () {
    it('should normalize paths correctly', function () {
        $normalizer = new PathNormalizer();

        expect($normalizer->normalizePath('  /foo//bar/../baz/ '))->toBe('/foo/baz');
        expect($normalizer->normalizePath('./foo/./bar/'))->toBe('/foo/bar');
        expect($normalizer->normalizePath('/foo/../../bar/'))->toBe('/bar');
        expect($normalizer->normalizePath('foo//bar///baz'))->toBe('/foo/bar/baz');
        expect($normalizer->normalizePath('/foo/./././bar/'))->toBe('/foo/bar');
        expect($normalizer->normalizePath('/foo/.././bar/../baz/'))->toBe('/baz');
        expect($normalizer->normalizePath('./../..'))->toBe('/');
    });
});
