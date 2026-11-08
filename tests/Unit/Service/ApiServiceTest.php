<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Http\Dto\Disk\DiskListDto;
use Kwaadpepper\LaravelStorageManager\Http\Response\ApiResponse;
use Kwaadpepper\LaravelStorageManager\Service\ApiService;
use Symfony\Component\HttpFoundation\JsonResponse;

describe('ApiService', function (): void {
    it('checks if request is allowed based on config and route name', function (): void {
        // GIVEN
        $enabledConfig = StorageManagerConfig::fromArray(
            ['enabled' => true],
            [],
            ['local' => ['driver' => 'local']]
        );
        $disabledConfig = StorageManagerConfig::fromArray(
            ['enabled' => false],
            [],
            ['local' => ['driver' => 'local']]
        );

        $enabledService  = new ApiService($enabledConfig);
        $disabledService = new ApiService($disabledConfig);

        $matchingRequest = Request::create('/storage-manager');
        $matchingRequest->setRouteResolver(fn (): mixed => new class ()
        {
            public function named(string ...$patterns): bool
            {
                return true;
            }
        });

        // WHEN
        $allowedWhenEnabled  = $enabledService->isAllowedToRequest($matchingRequest);
        $allowedWhenDisabled = $disabledService->isAllowedToRequest($matchingRequest);

        // THEN
        expect($allowedWhenEnabled)->toBeTrue();
        expect($allowedWhenDisabled)->toBeFalse();
    });

    it('checks if api request is allowed and detects json or sse', function (): void {
        // GIVEN
        $enabledConfig = StorageManagerConfig::fromArray(
            ['enabled' => true],
            [],
            ['local' => ['driver' => 'local']]
        );
        $disabledConfig = StorageManagerConfig::fromArray(
            ['enabled' => false],
            [],
            ['local' => ['driver' => 'local']]
        );

        $enabledService  = new ApiService($enabledConfig);
        $disabledService = new ApiService($disabledConfig);

        $apiRequest = Request::create('/storage-manager/api/fm/init', 'GET', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);
        $apiRequest->setRouteResolver(fn (): mixed => new class ()
        {
            public function named(string ...$patterns): bool
            {
                return true;
            }
        });

        // WHEN
        $isApi               = $enabledService->isApiRequest($apiRequest);
        $allowedWhenEnabled  = $enabledService->isAllowedToRequestApi($apiRequest);
        $allowedWhenDisabled = $disabledService->isAllowedToRequestApi($apiRequest);

        // THEN
        expect($isApi)->toBeTrue();
        expect($allowedWhenEnabled)->toBeTrue();
        expect($allowedWhenDisabled)->toBeFalse();
    });

    it('wraps JsonResponse and preserves existing ApiResponse', function (): void {
        // GIVEN
        $config = StorageManagerConfig::fromArray(
            ['enabled' => true],
            [],
            ['local' => ['driver' => 'local']]
        );
        $service             = new ApiService($config);
        $dto                 = new DiskListDto(['local']);
        $existingApiResponse = new ApiResponse($dto, 200);
        $rawJsonResponse     = new JsonResponse(['message' => 'ok'], 201);

        // WHEN
        $wrappedExisting   = $service->wrapResponse($existingApiResponse);
        $wrappedRaw        = $service->wrapResponse($rawJsonResponse);
        $isExistingWrapped = $service->isWrappedResponse($existingApiResponse);
        $isRawWrapped      = $service->isWrappedResponse($rawJsonResponse);

        // THEN
        expect($wrappedExisting)->toBe($existingApiResponse);
        expect($wrappedRaw)->toBeInstanceOf(ApiResponse::class);
        expect($wrappedRaw->getStatusCode())->toBe(201);
        expect($isExistingWrapped)->toBeTrue();
        expect($isRawWrapped)->toBeFalse();
    });
})->covers(ApiService::class);
