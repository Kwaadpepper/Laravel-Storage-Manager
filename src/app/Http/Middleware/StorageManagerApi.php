<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kwaadpepper\LaravelStorageManager\Service\ApiService;

class StorageManagerApi
{
    public function __construct(
        private readonly ApiService $apiService
    ) {
    }

    public function handle(Request $request, \Closure $next)
    {
        if (! $this->apiService->isAllowedToRequestApi($request)) {
            abort(403);
        }

        /** @var Response $response */
        $response = $next($request);

        return $this->apiService->wrapResponse($response);
    }
}
