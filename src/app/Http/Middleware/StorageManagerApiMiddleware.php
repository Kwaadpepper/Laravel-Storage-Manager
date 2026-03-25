<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Middleware;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kwaadpepper\LaravelStorageManager\Service\ApiService;

final class StorageManagerApiMiddleware
{
    public function __construct(
        private readonly ApiService $apiService
    ) {
    }

    public function handle(Request $request, \Closure $next): JsonResponse
    {
        if (! $this->apiService->isAllowedToRequestApi($request)) {
            abort(403);
        }

        $response = $next($request);

        if (! ($response instanceof JsonResponse)) {
            throw new \UnexpectedValueException('Expected JsonResponse from API route.');
        }

        $response->headers->set('Cache-Control', 'no-store');

        return $this->apiService->wrapResponse($response);
    }
}
