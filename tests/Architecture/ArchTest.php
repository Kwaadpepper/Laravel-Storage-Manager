<?php

declare(strict_types=1);

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\ServiceProvider;
use Kwaadpepper\LaravelStorageManager\Event\SmEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;

$libraryNamespace = 'Kwaadpepper\LaravelStorageManager';

describe('coding standards', function () use ($libraryNamespace): void {
    test('no debug globals are used')
        ->expect(['dd', 'dump', 'var_dump', 'ray'])
        ->not->toBeUsed();

    arch('all source files use strict types')
        ->expect($libraryNamespace)
        ->toUseStrictTypes();
});

describe('naming conventions', function () use ($libraryNamespace): void {
    arch('controllers have Controller suffix')
        ->expect("{$libraryNamespace}\Http\Controller")
        ->toHaveSuffix('Controller');

    arch('exceptions have Exception suffix')
        ->expect("{$libraryNamespace}\Exception")
        ->toHaveSuffix('Exception');

    arch('middlewares have Middleware suffix')
        ->expect("{$libraryNamespace}\Http\Middleware")
        ->toHaveSuffix('Middleware');

    arch('providers have Provider suffix')
        ->expect("{$libraryNamespace}\Provider")
        ->toHaveSuffix('Provider');

    arch('repositories have Repository suffix')
        ->expect("{$libraryNamespace}\Repository")
        ->toHaveSuffix('Repository');

    arch('services have Service suffix')
        ->expect("{$libraryNamespace}\Service")
        ->toHaveSuffix('Service');

});

describe('inheritance contracts', function () use ($libraryNamespace): void {
    arch('AuthenticationException extends HttpException')
        ->expect("{$libraryNamespace}\Exception\AuthenticationException")
        ->toExtend(HttpException::class);

    arch('FileOperationException extends HttpException')
        ->expect("{$libraryNamespace}\Exception\FileOperationException")
        ->toExtend(HttpException::class);

    arch('service provider extends base ServiceProvider')
        ->expect("{$libraryNamespace}\Provider")
        ->toExtend(ServiceProvider::class);

    arch('Rules implements ValidationRule')
        ->expect("{$libraryNamespace}\Rule")
        ->toImplement(ValidationRule::class);

    arch('Events implements SmEvent')
        ->expect("{$libraryNamespace}\Event")
        ->toImplement(SmEvent::class);
});
