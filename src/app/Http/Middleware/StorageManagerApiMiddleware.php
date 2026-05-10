<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Middleware;

use Illuminate\Http\Request;
use Kwaadpepper\LaravelStorageManager\Http\Response\ApiResponse;
use Kwaadpepper\LaravelStorageManager\Service\ApiService;
use Symfony\Component\HttpFoundation\JsonResponse;

final class StorageManagerApiMiddleware
{
    public function __construct(
        private readonly ApiService $apiService
    ) {
    }

    public function handle(Request $request, \Closure $next): ApiResponse
    {
        if (! $this->apiService->isAllowedToRequestApi($request)) {
            abort(403);
        }

        $response = $next($request);

        if ($response instanceof JsonResponse) {
            $response = $this->apiService->wrapResponse($response);
        }

        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
