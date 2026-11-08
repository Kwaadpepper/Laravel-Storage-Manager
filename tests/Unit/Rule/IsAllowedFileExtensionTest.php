<?php

declare(strict_types=1);

namespace Tests\Unit\Rule;

use Kwaadpepper\LaravelStorageManager\Config\UploadConfig;
use Kwaadpepper\LaravelStorageManager\Enum\DuplicatePolicy;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Rule\IsAllowedFileExtension;

describe('IsAllowedFileExtension', function (): void {
    it('passes when value is not a string', function (): void {
        // GIVEN
        $config = new UploadConfig(
            chunkMinSize: 1024,
            chunkMaxSize: 1048576,
            tempDisk: null,
            tempPath: 'temp',
            duplicatePolicy: DuplicatePolicy::OVERWRITE,
            sanitizeFilenames: true,
            allowedExtensions: ['jpg', 'png', 'pdf'],
        );
        $rule   = new IsAllowedFileExtension($config);
        $failed = false;
        $fail   = function (string $message) use (&$failed): void {
            $failed = true;
        };

        // WHEN
        $rule->validate('fileName', 12345, $fail);

        // THEN
        expect($failed)->toBeFalse();
    });

    it('passes when file extension is allowed', function (): void {
        // GIVEN
        $config = new UploadConfig(
            chunkMinSize: 1024,
            chunkMaxSize: 1048576,
            tempDisk: null,
            tempPath: 'temp',
            duplicatePolicy: DuplicatePolicy::OVERWRITE,
            sanitizeFilenames: true,
            allowedExtensions: ['jpg', 'png', 'pdf'],
        );
        $rule   = new IsAllowedFileExtension($config);
        $failed = false;
        $fail   = function (string $message) use (&$failed): void {
            $failed = true;
        };

        // WHEN
        $rule->validate('fileName', 'photo.jpg', $fail);

        // THEN
        expect($failed)->toBeFalse();
    });

    it('fails when file extension is not allowed', function (): void {
        // GIVEN
        $config = new UploadConfig(
            chunkMinSize: 1024,
            chunkMaxSize: 1048576,
            tempDisk: null,
            tempPath: 'temp',
            duplicatePolicy: DuplicatePolicy::OVERWRITE,
            sanitizeFilenames: true,
            allowedExtensions: ['jpg', 'png', 'pdf'],
        );
        $rule        = new IsAllowedFileExtension($config);
        $failMessage = '';
        $fail        = function (string $message) use (&$failMessage): void {
            $failMessage = $message;
        };

        // WHEN
        $rule->validate('fileName', 'archive.zip', $fail);

        // THEN
        expect($failMessage)->toBe(FileOperationError::FORBIDDEN_FILE_EXTENSION->message());
    });

    it('validates extension using target disk allowed extensions', function (): void {
        // GIVEN
        $config = new UploadConfig(
            chunkMinSize: 1024,
            chunkMaxSize: 1048576,
            tempDisk: null,
            tempPath: 'temp',
            duplicatePolicy: DuplicatePolicy::OVERWRITE,
            sanitizeFilenames: true,
            allowedExtensions: ['jpg', 'png', 'pdf'],
        );
        $mediaOnlyDisk = new Disk('s3', 's3_media', allowedExtensions: ['jpg', 'png']);
        $rule          = new IsAllowedFileExtension($config, $mediaOnlyDisk);
        $failMessages  = [];
        $fail          = function (string $message) use (&$failMessages): void {
            $failMessages[] = $message;
        };

        // WHEN
        $rule->validate('fileName', 'photo.jpg', $fail);
        $rule->validate('fileName', 'doc.pdf', $fail);

        // THEN
        expect($failMessages)->toHaveCount(1);
        expect($failMessages[0])->toBe(FileOperationError::FORBIDDEN_FILE_EXTENSION->message());
    });
})->covers(IsAllowedFileExtension::class);
