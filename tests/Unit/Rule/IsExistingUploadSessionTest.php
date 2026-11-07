<?php

declare(strict_types=1);

namespace Tests\Unit\Rule;

use Illuminate\Support\Facades\Lang;
use Kwaadpepper\LaravelStorageManager\Lib\Upload\UploadSessionService;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadId;
use Kwaadpepper\LaravelStorageManager\Rule\IsExistingUploadSession;
use Mockery;

describe('IsExistingUploadSession', function (): void {
    it('passes when upload session exists', function (): void {
        // GIVEN
        $uploadId = UploadId::generate();
        $service  = Mockery::mock(UploadSessionService::class);
        $service->shouldReceive('sessionExists')->once()->andReturn(true);
        $rule   = new IsExistingUploadSession($service);
        $failed = false;
        $fail   = function (string $message) use (&$failed): void {
            $failed = true;
        };

        // WHEN
        $rule->validate('upload_id', $uploadId->value, $fail);

        // THEN
        expect($failed)->toBeFalse();
    });

    it('fails when value is not a string', function (): void {
        // GIVEN
        $service     = Mockery::mock(UploadSessionService::class);
        $rule        = new IsExistingUploadSession($service);
        $failMessage = '';
        $fail        = function (string $message) use (&$failMessage): void {
            $failMessage = $message;
        };

        // WHEN
        $rule->validate('upload_id', ['array'], $fail);

        // THEN
        expect($failMessage)->toBe((string) Lang::get('storage-manager::storage-manager.validation.invalid_upload_id'));
    });

    it('fails when value is not a valid upload id', function (): void {
        // GIVEN
        $service     = Mockery::mock(UploadSessionService::class);
        $rule        = new IsExistingUploadSession($service);
        $failMessage = '';
        $fail        = function (string $message) use (&$failMessage): void {
            $failMessage = $message;
        };

        // WHEN
        $rule->validate('upload_id', 'not-a-valid-uuid', $fail);

        // THEN
        expect($failMessage)->toBe((string) Lang::get('storage-manager::storage-manager.validation.invalid_upload_id'));
    });

    it('fails when upload session does not exist', function (): void {
        // GIVEN
        $uploadId = UploadId::generate();
        $service  = Mockery::mock(UploadSessionService::class);
        $service->shouldReceive('sessionExists')->once()->andReturn(false);
        $rule        = new IsExistingUploadSession($service);
        $failMessage = '';
        $fail        = function (string $message) use (&$failMessage): void {
            $failMessage = $message;
        };

        // WHEN
        $rule->validate('upload_id', $uploadId->value, $fail);

        // THEN
        expect($failMessage)->toBe((string) Lang::get('storage-manager::storage-manager.validation.upload_session_not_found'));
    });
})->covers(IsExistingUploadSession::class);
