<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Middleware;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kwaadpepper\LaravelStorageManager\Exception\AuthenticationException;
use Kwaadpepper\LaravelStorageManager\Http\Response\ApiResponse;
use Kwaadpepper\LaravelStorageManager\Service\ApiService;

final class StorageManagerMiddleware
{
    public function __construct(
        private readonly ApiService $apiService
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(Request):(Response|ApiResponse)  $next
     *
     * @throws AuthenticationException
     */
    public function handle(Request $request, \Closure $next): Response | ApiResponse
    {
        if (! $this->apiService->isAllowedToRequest($request)) {
            abort(ApiResponse::HTTP_FORBIDDEN);
        }

        $jsonMimeTypes = 'application/json';

        $originalAcceptHeader      = $request->headers->get('Accept');
        $originalContentTypeHeader = $request->headers->get('Content-Type');
        $wantedJson                = $request->wantsJson();
        $request->headers->set('Accept', $jsonMimeTypes);
        $request->headers->set('Content-Type', $jsonMimeTypes);

        $response = $next($request);

        if ($response instanceof JsonResponse) {
            $response = $this->apiService->wrapResponse($response);
        }

        $request->headers->set('Accept', $originalAcceptHeader);
        $request->headers->set('Content-Type', $originalContentTypeHeader);

        if (! $wantedJson && $response->getStatusCode() === ApiResponse::HTTP_UNAUTHORIZED) {
            throw new AuthenticationException();
        }

        return $response;
    }
}
