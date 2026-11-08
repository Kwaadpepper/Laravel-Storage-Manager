<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Pagination\PageSize;

describe('PageSize Value Object', function (): void {
    it('creates page size with default value', function (): void {
        // GIVEN
        $expected = 50;

        // WHEN
        $pageSize = new PageSize();

        // THEN
        expect($pageSize->value)->toBe($expected)
            ->and($pageSize->value())->toBe($expected)
            ->and((string) $pageSize)->toBe('50')
            ->and($pageSize->jsonSerialize())->toBe($expected);
    });

    it('creates page size using of factory method', function (): void {
        // GIVEN
        $value = 25;

        // WHEN
        $pageSize = PageSize::of($value);

        // THEN
        expect($pageSize->value)->toBe(25)
            ->and($pageSize->value())->toBe(25)
            ->and((string) $pageSize)->toBe('25');
    });

    it('creates page size using default factory method', function (): void {
        // GIVEN
        $expected = 50;

        // WHEN
        $pageSize = PageSize::default();

        // THEN
        expect($pageSize->value)->toBe($expected)
            ->and($pageSize->value())->toBe($expected);
    });

    it('throws exception when value is zero or negative', function (int $invalidValue): void {
        // GIVEN
        $value = $invalidValue;

        // WHEN
        $action = fn (): PageSize => new PageSize($value);

        // THEN
        expect($action)
            ->toThrow(\InvalidArgumentException::class, 'Page size must be greater than 0.');
    })->with([0, -1, -50]);

    it('throws exception when value exceeds max page size', function (): void {
        // GIVEN
        $invalidValue = PageSize::MAX_PAGE_SIZE + 1;

        // WHEN
        $action = fn (): PageSize => new PageSize($invalidValue);

        // THEN
        expect($action)
            ->toThrow(
                \InvalidArgumentException::class,
                'Page size must be less than or equal to ' . PageSize::MAX_PAGE_SIZE . '.',
            );
    });

    it('checks equality with another PageSize instance', function (): void {
        // GIVEN
        $size1 = new PageSize(20);
        $size2 = new PageSize(20);
        $size3 = new PageSize(30);

        // WHEN
        $equalsSame  = $size1->equals($size2);
        $equalsDiff  = $size1->equals($size3);
        $equalsNull  = $size1->equals(null);
        $equalsOther = $size1->equals('20');

        // THEN
        expect($equalsSame)->toBeTrue()
            ->and($equalsDiff)->toBeFalse()
            ->and($equalsNull)->toBeFalse()
            ->and($equalsOther)->toBeFalse();
    });
})->covers(PageSize::class);
