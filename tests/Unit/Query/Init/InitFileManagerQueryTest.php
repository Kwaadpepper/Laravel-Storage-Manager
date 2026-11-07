<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Kwaadpepper\LaravelStorageManager\Event\FileManagerShowed;
use Kwaadpepper\LaravelStorageManager\Query\Init\InitFileManagerInput;
use Kwaadpepper\LaravelStorageManager\Query\Init\InitFileManagerQuery;

describe('InitFileManagerQuery', function (): void {
    it('dispatches FileManagerShowed and returns chunk configuration', function (): void {
        // GIVEN
        Event::fake([FileManagerShowed::class]);
        $query = resolve(InitFileManagerQuery::class);

        // WHEN
        $result = $query(new InitFileManagerInput());

        // THEN
        expect($result)->toHaveKeys(['chunkMinSize', 'chunkMaxSize']);
        expect($result['chunkMinSize'])->toBeGreaterThan(0);
        Event::assertDispatched(FileManagerShowed::class);
    });
})->covers(InitFileManagerQuery::class);
