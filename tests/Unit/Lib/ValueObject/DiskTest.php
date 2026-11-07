<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Exception\IllegalDomainStateException;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;

describe('Disk', function (): void {
    it('stores all constructor arguments as readonly properties', function (): void {
        // GIVEN
        $driver   = 'local';
        $name     = 'my-disk';
        $throw    = true;
        $report   = false;
        $readOnly = true;

        // WHEN
        $disk = new Disk($driver, $name, $throw, $report, $readOnly);

        // THEN
        expect($disk->driver)->toBe($driver);
        expect($disk->name)->toBe($name);
        expect($disk->throw)->toBeTrue();
        expect($disk->report)->toBeFalse();
        expect($disk->readOnly)->toBeTrue();
    });

    it('throws a DomainException when driver is empty', function (): void {
        // GIVEN
        $emptyDriver = '';
        $name        = 'my-disk';
        $throw       = true;
        $report      = false;

        // WHEN
        $action = fn (): Disk => new Disk($emptyDriver, $name, $throw, $report);

        // THEN
        expect($action)
            ->toThrow(IllegalDomainStateException::class, 'Disk driver cannot be empty.');
    });

    it('throws a DomainException when name is empty', function (): void {
        // GIVEN
        $emptyName = '';
        $driver    = 'local';
        $throw     = true;
        $report    = false;

        // WHEN
        $action = fn (): Disk => new Disk($driver, $emptyName, $throw, $report);

        // THEN
        expect($action)
            ->toThrow(IllegalDomainStateException::class, 'Disk name cannot be empty.');
    });

    it('assertWritable does nothing on a writable disk', function (): void {
        // GIVEN
        $disk = new Disk('local', 'my-disk', false, false, false);

        // WHEN / THEN - no exception
        $disk->assertWritable();
        expect(true)->toBeTrue();
    });

    it('assertWritable throws FileOperationException on a read-only disk', function (): void {
        // GIVEN
        $disk = new Disk('local', 'my-disk', false, false, true);

        // WHEN
        $action = static function () use ($disk): void {
            $disk->assertWritable();
        };

        // THEN
        expect($action)->toThrow(
            FileOperationException::class
        );
    });
});
