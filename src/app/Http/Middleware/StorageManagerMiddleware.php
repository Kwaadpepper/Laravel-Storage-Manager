<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kwaadpepper\LaravelStorageManager\Http\Response\ApiResponse;
use Kwaadpepper\LaravelStorageManager\Service\ApiService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class StorageManagerMiddleware
{
    public function __construct(
        private readonly ApiService $apiService
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(Request):(Response|BinaryFileResponse|StreamedResponse|ApiResponse)  $next
     */
    public function handle(Request $request, \Closure $next): Response | BinaryFileResponse | StreamedResponse | ApiResponse
    {
        if (! $this->apiService->isAllowedToRequest($request)) {
            abort(ApiResponse::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
