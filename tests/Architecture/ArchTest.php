<?php

declare(strict_types=1);

use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;

describe('coding standards', function (): void {
    test('no debug globals are used')
        ->expect(['dd', 'dump', 'var_dump', 'ray'])
        ->not->toBeUsed();

    arch('all source files use strict types')
        ->expect('Kwaadpepper\LaravelStorageManager')
        ->toUseStrictTypes();
});

describe('naming conventions', function (): void {
    arch('controllers have Controller suffix')
        ->expect('Kwaadpepper\LaravelStorageManager\Http\Controller')
        ->toHaveSuffix('Controller');

    arch('middlewares have Middleware suffix')
        ->expect('Kwaadpepper\LaravelStorageManager\Http\Middleware')
        ->toHaveSuffix('Middleware');

    arch('exceptions have Exception suffix')
        ->expect('Kwaadpepper\LaravelStorageManager\Exception')
        ->toHaveSuffix('Exception');
});

describe('inheritance contracts', function (): void {
    arch('AuthenticationException extends HttpException')
        ->expect('Kwaadpepper\LaravelStorageManager\Exception\AuthenticationException')
        ->toExtend(HttpException::class);

    arch('FileOperationException extends HttpException')
        ->expect('Kwaadpepper\LaravelStorageManager\Exception\FileOperationException')
        ->toExtend(HttpException::class);

    arch('service provider extends base ServiceProvider')
        ->expect('Kwaadpepper\LaravelStorageManager\Provider')
        ->toExtend(ServiceProvider::class);
});
