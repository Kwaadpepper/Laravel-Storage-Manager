<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Pagination\PageNumber;

describe('PageNumber Value Object', function (): void {
    it('creates page number with default value', function (): void {
        // GIVEN
        $expected = 1;

        // WHEN
        $pageNumber = new PageNumber();

        // THEN
        expect($pageNumber->value)->toBe($expected)
            ->and($pageNumber->value())->toBe($expected)
            ->and((string) $pageNumber)->toBe('1')
            ->and($pageNumber->jsonSerialize())->toBe($expected);
    });

    it('creates page number using of factory method', function (): void {
        // GIVEN
        $value = 5;

        // WHEN
        $pageNumber = PageNumber::of($value);

        // THEN
        expect($pageNumber->value)->toBe(5)
            ->and($pageNumber->value())->toBe(5)
            ->and((string) $pageNumber)->toBe('5');
    });

    it('creates page number using default factory method', function (): void {
        // GIVEN
        $expected = 1;

        // WHEN
        $pageNumber = PageNumber::default();

        // THEN
        expect($pageNumber->value)->toBe($expected)
            ->and($pageNumber->value())->toBe($expected);
    });

    it('throws exception when value is zero or negative', function (int $invalidValue): void {
        // GIVEN
        $value = $invalidValue;

        // WHEN
        $action = fn (): PageNumber => new PageNumber($value);

        // THEN
        expect($action)
            ->toThrow(\InvalidArgumentException::class, 'Page number must be greater than 0.');
    })->with([0, -1, -50]);

    it('checks equality with another PageNumber instance', function (): void {
        // GIVEN
        $page1 = new PageNumber(2);
        $page2 = new PageNumber(2);
        $page3 = new PageNumber(3);

        // WHEN
        $equalsSame  = $page1->equals($page2);
        $equalsDiff  = $page1->equals($page3);
        $equalsNull  = $page1->equals(null);
        $equalsOther = $page1->equals('2');

        // THEN
        expect($equalsSame)->toBeTrue()
            ->and($equalsDiff)->toBeFalse()
            ->and($equalsNull)->toBeFalse()
            ->and($equalsOther)->toBeFalse();
    });
})->covers(PageNumber::class);
