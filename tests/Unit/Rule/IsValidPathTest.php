<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Rule\IsValidPath;

describe('IsValidPath', function (): void {
    it('passes for valid path and fails for invalid path', function (): void {
        // GIVEN
        $rule         = new IsValidPath();
        $passedErrors = [];
        $failedErrors = [];
        $passClosure  = function (string $msg) use (&$passedErrors): void {
            $passedErrors[] = $msg;
        };
        $failClosure = function (string $msg) use (&$failedErrors): void {
            $failedErrors[] = $msg;
        };

        // WHEN
        $rule->validate('path', '/valid/path.txt', $passClosure);
        $rule->validate('path', null, $failClosure);

        // THEN
        expect($passedErrors)->toBeEmpty();
        expect($failedErrors)->not->toBeEmpty();
    });
})->covers(IsValidPath::class);
