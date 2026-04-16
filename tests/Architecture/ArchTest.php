<?php

declare(strict_types=1);

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\ServiceProvider;
use Kwaadpepper\LaravelStorageManager\Event\SmEvent;
use Kwaadpepper\LaravelStorageManager\Http\Dto\Dto;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

$libraryNamespace = 'Kwaadpepper\LaravelStorageManager';

/**
 * @return list<class-string>
 */
function controllerClassNames(string $libraryNamespace): array
{
    $controllerPath  = __DIR__ . '/../../src/app/Http/Controller';
    $controllerFiles = glob($controllerPath . '/*Controller.php') ?: [];

    return array_map(
        static fn (string $controllerFile): string => $libraryNamespace . '\\Http\\Controller\\' . pathinfo($controllerFile, PATHINFO_FILENAME),
        $controllerFiles,
    );
}

function assertControllerPublicMethodsReturnJsonResponse(string $controllerClass): void
{
    $reflection = new ReflectionClass($controllerClass);

    foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        if ($method->getDeclaringClass()->getName() !== $controllerClass || $method->isConstructor()) {
            continue;
        }

        assertMethodReturnsJsonResponse($method, $controllerClass);
    }
}

function assertMethodReturnsJsonResponse(ReflectionMethod $method, string $controllerClass): void
{
    $returnType = $method->getReturnType();

    expect($returnType)
        ->not->toBeNull(sprintf('%s::%s() must declare a JsonResponse return type.', $controllerClass, $method->getName()));

    expect($returnType)
        ->toBeInstanceOf(ReflectionNamedType::class, sprintf('%s::%s() must not use union/intersection return types.', $controllerClass, $method->getName()));

    $returnTypeName = $returnType instanceof ReflectionNamedType ? $returnType->getName() : null;

    expect(
        is_string($returnTypeName) && is_a($returnTypeName, JsonResponse::class, true)
    )->toBeTrue(sprintf('%s::%s() must return %s or a subtype.', $controllerClass, $method->getName(), JsonResponse::class));
}

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

    arch('dto have Dto suffix')
        ->expect("{$libraryNamespace}\Http\Dto")
        ->toHaveSuffix('Dto');

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

    arch('Dto implements Dto interface')
        ->expect("{$libraryNamespace}\Http\Dto")
        ->toImplement(Dto::class);
});

describe('controller contracts', function () use ($libraryNamespace): void {
    test('all public controller methods return JsonResponse', function () use ($libraryNamespace): void {
        foreach (controllerClassNames($libraryNamespace) as $controllerClass) {
            assertControllerPublicMethodsReturnJsonResponse($controllerClass);
        }
    });
});
