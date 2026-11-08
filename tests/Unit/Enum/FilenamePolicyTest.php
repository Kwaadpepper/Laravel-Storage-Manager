<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Enum\FilenamePolicy;

describe('FilenamePolicy', function (): void {
    it('returns strict illegal characters regex including colon', function (): void {
        // GIVEN
        $policy = FilenamePolicy::STRICT;

        // WHEN
        $regex = $policy->illegalCharsRegex();

        // THEN
        expect($regex)->toBe('/[<>:"\/\\\\|?*\x00-\x1F\x7F]/u');
        expect(preg_match($regex, 'file:name.txt'))->toBe(1);
    });

    it('returns permissive illegal characters regex excluding colon', function (): void {
        // GIVEN
        $policy = FilenamePolicy::PERMISSIVE;

        // WHEN
        $regex = $policy->illegalCharsRegex();

        // THEN
        expect($regex)->toBe('/[<>"\/\\\\|?*\x00-\x1F\x7F]/u');
        expect(preg_match($regex, 'file:name.txt'))->toBe(0);
        expect(preg_match($regex, 'file*name.txt'))->toBe(1);
    });
})->covers(FilenamePolicy::class);
