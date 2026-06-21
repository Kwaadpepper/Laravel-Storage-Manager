<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Exception\IllegalDomainStateException;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;

describe('Disk', function (): void {
    it('stores all constructor arguments as readonly properties', function (): void {
        // Given
        $driver   = 'local';
        $name     = 'my-disk';
        $throw    = true;
        $report   = false;
        $readOnly = true;

        // When
        $disk = new Disk($driver, $name, $throw, $report, $readOnly);

        // Then
        expect($disk->driver)->toBe($driver)
            ->and($disk->name)->toBe($name)
            ->and($disk->throw)->toBeTrue()
            ->and($disk->report)->toBeFalse()
            ->and($disk->readOnly)->toBeTrue();
    });

    it('throws a DomainException when driver is empty', function (): void {
        // Given
        $emptyDriver = '';
        $name        = 'my-disk';
        $throw       = true;
        $report      = false;

        // When / Then
        expect(fn () => new Disk($emptyDriver, $name, $throw, $report))
            ->toThrow(IllegalDomainStateException::class, 'Disk driver cannot be empty.');
    });

    it('throws a DomainException when name is empty', function (): void {
        // Given
        $emptyName = '';
        $driver    = 'local';
        $throw     = true;
        $report    = false;

        // When / Then
        expect(fn () => new Disk($driver, $emptyName, $throw, $report))
            ->toThrow(IllegalDomainStateException::class, 'Disk name cannot be empty.');
    });
});
