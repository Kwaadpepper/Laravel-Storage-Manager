<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Middleware;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kwaadpepper\LaravelStorageManager\Exception\AuthenticationException;
use Kwaadpepper\LaravelStorageManager\Service\ApiService;

final class StorageManagerMiddleware
{
    public function __construct(
        private readonly ApiService $apiService
    ) {
    }

    public function handle(Request $request, \Closure $next)
    {
        if (! $this->apiService->isAllowedToRequest($request)) {
            abort(JsonResponse::HTTP_FORBIDDEN);
        }

        $jsonMimeTypes = 'application/json';

        $originalAcceptHeader      = $request->headers->get('Accept');
        $originalContentTypeHeader = $request->headers->get('Content-Type');
        $isApiRequest              = $this->apiService->isApiRequest($request);
        $wantedJson                = $request->wantsJson();
        $request->headers->set('Accept', $jsonMimeTypes);
        $request->headers->set('Content-Type', $jsonMimeTypes);

        $response = $next($request);

        $request->headers->set('Accept', $originalAcceptHeader);
        $request->headers->set('Content-Type', $originalContentTypeHeader);

        if (! $wantedJson && $response->getStatusCode() === JsonResponse::HTTP_UNAUTHORIZED) {
            throw new AuthenticationException();
        }

        if ($isApiRequest) {
            return $this->apiService->wrapResponse($response);
        }

        return $response;
    }
}
