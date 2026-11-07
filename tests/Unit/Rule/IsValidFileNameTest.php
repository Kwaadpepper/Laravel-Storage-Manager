<?php

declare(strict_types=1);

namespace Tests\Unit\Rule;

use Illuminate\Support\Facades\Lang;
use Kwaadpepper\LaravelStorageManager\Rule\IsValidFileName;

describe('IsValidFileName', function (): void {
    it('passes for a valid file name', function (): void {
        // GIVEN
        $rule   = new IsValidFileName();
        $failed = false;
        $fail   = function (string $message) use (&$failed): void {
            $failed = true;
        };

        // WHEN
        $rule->validate('name', 'document.pdf', $fail);

        // THEN
        expect($failed)->toBeFalse();
    });

    it('fails when file name is empty', function (): void {
        // GIVEN
        $rule        = new IsValidFileName();
        $failMessage = '';
        $fail        = function (string $message) use (&$failMessage): void {
            $failMessage = $message;
        };

        // WHEN
        $rule->validate('name', '', $fail);

        // THEN
        expect($failMessage)->toBe((string) Lang::get('storage-manager::storage-manager.validation.invalid_file_name'));
    });

    it('fails when file name is not a string', function (): void {
        // GIVEN
        $rule        = new IsValidFileName();
        $failMessage = '';
        $fail        = function (string $message) use (&$failMessage): void {
            $failMessage = $message;
        };

        // WHEN
        $rule->validate('name', null, $fail);

        // THEN
        expect($failMessage)->toBe((string) Lang::get('storage-manager::storage-manager.validation.invalid_file_name'));
    });

    it('fails when file name contains forward slash', function (): void {
        // GIVEN
        $rule        = new IsValidFileName();
        $failMessage = '';
        $fail        = function (string $message) use (&$failMessage): void {
            $failMessage = $message;
        };

        // WHEN
        $rule->validate('name', 'sub/file.txt', $fail);

        // THEN
        expect($failMessage)->toBe((string) Lang::get('storage-manager::storage-manager.validation.invalid_file_name'));
    });

    it('fails when file name contains backslash', function (): void {
        // GIVEN
        $rule        = new IsValidFileName();
        $failMessage = '';
        $fail        = function (string $message) use (&$failMessage): void {
            $failMessage = $message;
        };

        // WHEN
        $rule->validate('name', 'sub\\file.txt', $fail);

        // THEN
        expect($failMessage)->toBe((string) Lang::get('storage-manager::storage-manager.validation.invalid_file_name'));
    });
})->covers(IsValidFileName::class);
