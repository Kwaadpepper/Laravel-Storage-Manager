<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Pagination\PageNumber;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Pagination\PageSize;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Pagination\Pagination;

describe('Pagination Value Object', function (): void {
    it('creates pagination using ofPage with default page size', function (): void {
        // GIVEN
        $pageNumber = new PageNumber(1);

        // WHEN
        $pagination = Pagination::ofPage($pageNumber);

        // THEN
        expect($pagination->from())->toBe(0)
            ->and($pagination->to())->toBe(49)
            ->and($pagination->limit())->toBe(50)
            ->and($pagination->offset())->toBe(0)
            ->and($pagination->pageNumber()->value)->toBe(1)
            ->and($pagination->pageSize()->value)->toBe(50);
    });

    it('creates pagination using ofPage with explicit page size', function (): void {
        // GIVEN
        $pageNumber = new PageNumber(2);
        $pageSize   = new PageSize(20);

        // WHEN
        $pagination = Pagination::ofPage($pageNumber, $pageSize);

        // THEN
        expect($pagination->from())->toBe(20)
            ->and($pagination->to())->toBe(39)
            ->and($pagination->limit())->toBe(20)
            ->and($pagination->offset())->toBe(20)
            ->and($pagination->pageNumber()->value)->toBe(2)
            ->and($pagination->pageSize()->value)->toBe(20);
    });

    it('creates pagination using ofOffsetAndLimit', function (): void {
        // GIVEN
        $offset = 30;
        $limit  = 15;

        // WHEN
        $pagination = Pagination::ofOffsetAndLimit($offset, $limit);

        // THEN
        expect($pagination->from())->toBe(30)
            ->and($pagination->to())->toBe(44)
            ->and($pagination->limit())->toBe(15)
            ->and($pagination->offset())->toBe(30)
            ->and($pagination->pageNumber()->value)->toBe(3)
            ->and($pagination->pageSize()->value)->toBe(15);
    });

    it('clamps negative offset and bounds limit in ofOffsetAndLimit', function (): void {
        // GIVEN
        $negativeOffset = -10;
        $zeroLimit      = 0;

        // WHEN
        $pagination = Pagination::ofOffsetAndLimit($negativeOffset, $zeroLimit);

        // THEN
        expect($pagination->from())->toBe(0)
            ->and($pagination->to())->toBe(0)
            ->and($pagination->limit())->toBe(1)
            ->and($pagination->pageSize()->value)->toBe(1);
    });

    it('creates pagination using firstPage helper with default size', function (): void {
        // GIVEN
        $expectedLimit = 50;

        // WHEN
        $pagination = Pagination::firstPage();

        // THEN
        expect($pagination->from())->toBe(0)
            ->and($pagination->to())->toBe(49)
            ->and($pagination->limit())->toBe($expectedLimit)
            ->and($pagination->pageNumber()->value)->toBe(1);
    });

    it('creates pagination using firstPage helper with explicit size', function (): void {
        // GIVEN
        $pageSize = new PageSize(10);

        // WHEN
        $pagination = Pagination::firstPage($pageSize);

        // THEN
        expect($pagination->from())->toBe(0)
            ->and($pagination->to())->toBe(9)
            ->and($pagination->limit())->toBe(10)
            ->and($pagination->pageSize()->value)->toBe(10);
    });

    it('converts to string representation', function (): void {
        // GIVEN
        $pagination = Pagination::ofOffsetAndLimit(10, 5);

        // WHEN
        $result = (string) $pagination;

        // THEN
        expect($result)->toBe('Pagination{from=10, to=14}');
    });

    it('checks equality with another Pagination instance', function (): void {
        // GIVEN
        $p1 = Pagination::ofPage(new PageNumber(1), new PageSize(20));
        $p2 = Pagination::ofPage(new PageNumber(1), new PageSize(20));
        $p3 = Pagination::ofPage(new PageNumber(2), new PageSize(20));

        // WHEN
        $equalsSame  = $p1->equals($p2);
        $equalsDiff  = $p1->equals($p3);
        $equalsNull  = $p1->equals(null);
        $equalsOther = $p1->equals('pagination');

        // THEN
        expect($equalsSame)->toBeTrue()
            ->and($equalsDiff)->toBeFalse()
            ->and($equalsNull)->toBeFalse()
            ->and($equalsOther)->toBeFalse();
    });
})->covers(Pagination::class);
