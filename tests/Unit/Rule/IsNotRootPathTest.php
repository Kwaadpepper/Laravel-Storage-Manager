<?php

declare(strict_types=1);

namespace Tests\Unit\Rule;

use Illuminate\Support\Facades\Lang;
use Kwaadpepper\LaravelStorageManager\Rule\IsNotRootPath;

describe('IsNotRootPath', function (): void {
    it('passes for non-root paths', function (): void {
        // GIVEN
        $rule   = new IsNotRootPath();
        $failed = false;
        $fail   = function (string $message) use (&$failed): void {
            $failed = true;
        };

        // WHEN
        $rule->validate('path', '/documents/file.txt', $fail);

        // THEN
        expect($failed)->toBeFalse();
    });

    it('passes when value is not a string', function (): void {
        // GIVEN
        $rule   = new IsNotRootPath();
        $failed = false;
        $fail   = function (string $message) use (&$failed): void {
            $failed = true;
        };

        // WHEN
        $rule->validate('path', 12345, $fail);

        // THEN
        expect($failed)->toBeFalse();
    });

    it('fails when path is a slash', function (): void {
        // GIVEN
        $rule        = new IsNotRootPath();
        $failMessage = '';
        $fail        = function (string $message) use (&$failMessage): void {
            $failMessage = $message;
        };

        // WHEN
        $rule->validate('path', '/', $fail);

        // THEN
        expect($failMessage)->toBe((string) Lang::get('storage-manager::storage-manager.validation.cannot_delete_root_path'));
    });

    it('fails when path is empty string', function (): void {
        // GIVEN
        $rule        = new IsNotRootPath();
        $failMessage = '';
        $fail        = function (string $message) use (&$failMessage): void {
            $failMessage = $message;
        };

        // WHEN
        $rule->validate('path', '', $fail);

        // THEN
        expect($failMessage)->toBe((string) Lang::get('storage-manager::storage-manager.validation.cannot_delete_root_path'));
    });
})->covers(IsNotRootPath::class);
