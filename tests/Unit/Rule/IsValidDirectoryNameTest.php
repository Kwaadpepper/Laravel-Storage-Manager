<?php

declare(strict_types=1);

namespace Tests\Unit\Rule;

use Illuminate\Support\Facades\Lang;
use Kwaadpepper\LaravelStorageManager\Rule\IsValidDirectoryName;

describe('IsValidDirectoryName', function (): void {
    it('passes for a valid directory name', function (): void {
        // GIVEN
        $rule   = new IsValidDirectoryName();
        $failed = false;
        $fail   = function (string $message) use (&$failed): void {
            $failed = true;
        };

        // WHEN
        $rule->validate('name', 'valid-directory', $fail);

        // THEN
        expect($failed)->toBeFalse();
    });

    it('fails when directory name is empty', function (): void {
        // GIVEN
        $rule        = new IsValidDirectoryName();
        $failMessage = '';
        $fail        = function (string $message) use (&$failMessage): void {
            $failMessage = $message;
        };

        // WHEN
        $rule->validate('name', '', $fail);

        // THEN
        expect($failMessage)->toBe((string) Lang::get('storage-manager::storage-manager.validation.invalid_directory_name'));
    });

    it('fails when directory name is not a string', function (): void {
        // GIVEN
        $rule        = new IsValidDirectoryName();
        $failMessage = '';
        $fail        = function (string $message) use (&$failMessage): void {
            $failMessage = $message;
        };

        // WHEN
        $rule->validate('name', 12345, $fail);

        // THEN
        expect($failMessage)->toBe((string) Lang::get('storage-manager::storage-manager.validation.invalid_directory_name'));
    });

    it('fails when directory name contains forward slash', function (): void {
        // GIVEN
        $rule        = new IsValidDirectoryName();
        $failMessage = '';
        $fail        = function (string $message) use (&$failMessage): void {
            $failMessage = $message;
        };

        // WHEN
        $rule->validate('name', 'foo/bar', $fail);

        // THEN
        expect($failMessage)->toBe((string) Lang::get('storage-manager::storage-manager.validation.invalid_directory_name'));
    });

    it('fails when directory name contains backslash', function (): void {
        // GIVEN
        $rule        = new IsValidDirectoryName();
        $failMessage = '';
        $fail        = function (string $message) use (&$failMessage): void {
            $failMessage = $message;
        };

        // WHEN
        $rule->validate('name', 'foo\\bar', $fail);

        // THEN
        expect($failMessage)->toBe((string) Lang::get('storage-manager::storage-manager.validation.invalid_directory_name'));
    });
})->covers(IsValidDirectoryName::class);
