<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Request;

use Illuminate\Support\Facades\Config;
use Illuminate\Validation\ValidationException;
use Kwaadpepper\LaravelStorageManager\Http\Request\RequestWithDisk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;

describe('RequestWithDisk', function (): void {
    beforeEach(function (): void {
        Config::set('storage-manager.disks.available', ['local']);
        Config::set('storage-manager.disks.default', 'local');
    });

    it('returns Disk instance when disk parameter is valid', function (): void {
        // GIVEN
        $request = new class () extends RequestWithDisk
        {
        };
        $request->initialize(['disk' => 'local']);
        $request->prepareForValidation();

        // WHEN
        $disk = $request->getDisk();

        // THEN
        expect($disk)->toBeInstanceOf(Disk::class);
        expect($disk->name)->toBe('local');
    });

    it('throws ValidationException when getDisk is called with an invalid disk', function (): void {
        // GIVEN
        $request = new class () extends RequestWithDisk
        {
        };
        $request->initialize(['disk' => 'nonexistent_disk']);
        $request->prepareForValidation();

        // WHEN
        $action = fn (): Disk => $request->getDisk();

        // THEN
        expect($action)->toThrow(ValidationException::class);
    });

    it('throws ValidationException when getDisk is called with empty disk', function (): void {
        // GIVEN
        $request = new class () extends RequestWithDisk
        {
        };
        $request->initialize([]);
        $request->prepareForValidation();

        // WHEN
        $action = fn (): Disk => $request->getDisk();

        // THEN
        expect($action)->toThrow(ValidationException::class);
    });
})->covers(RequestWithDisk::class);
