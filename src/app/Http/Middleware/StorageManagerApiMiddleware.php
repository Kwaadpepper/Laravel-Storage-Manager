<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Middleware;

use Illuminate\Http\Request;
use Kwaadpepper\LaravelStorageManager\Http\Exception\ApiExceptionHandler;
use Kwaadpepper\LaravelStorageManager\Http\Response\ApiResponse;
use Kwaadpepper\LaravelStorageManager\Service\ApiService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;

final class StorageManagerApiMiddleware
{
    public function __construct(
        private readonly ApiService $apiService,
        private readonly ApiExceptionHandler $apiExceptionHandler,
    ) {
    }

    public function handle(Request $request, \Closure $next): ApiResponse
    {
        if (! $this->apiService->isAllowedToRequestApi($request)) {
            abort(403);
        }

        try {
            $response = $next($request);
        } catch (Throwable $exception) {
            $response = $this->apiExceptionHandler->toApiResponse($exception);
        }

        if ($response instanceof JsonResponse) {
            $normalizedErrorResponse = $this->apiExceptionHandler->toApiResponseFromRenderedJsonResponse($response);
            $response = $normalizedErrorResponse ?? $this->apiService->wrapResponse($response);
        }

        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
