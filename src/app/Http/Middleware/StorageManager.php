<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Middleware;

use Illuminate\Http\Request;
use Kwaadpepper\LaravelStorageManager\Service\ApiService;

class StorageManager
{
    public function __construct(
        private readonly ApiService $apiService
    ) {
    }

    public function handle(Request $request, \Closure $next)
    {
        if (! $this->apiService->isAllowedToRequest($request)) {
            abort(403);
        }

        return $next($request);
    }
}
