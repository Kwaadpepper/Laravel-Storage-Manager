<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Service;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Kwaadpepper\LaravelStorageManager\Repository\ConfigRepository;
use Symfony\Component\HttpFoundation\Response;

class ApiService
{
    public function __construct(
        private readonly ConfigRepository $configRepository
    ) {
    }

    public function isAllowedToRequest(Request $request): bool
    {
        if (! $this->configRepository->isEnabled()) {
            return false;
        }

        return $request->routeIs('storage-manager.*');
    }

    public function isAllowedToRequestApi(Request $request): bool
    {
        if (! $this->configRepository->isEnabled()) {
            return false;
        }

        return $this->isApiRequest($request);
    }

    public function isApiRequest(Request $request): bool
    {
        return $request->expectsJson() && $request->routeIs('storage-manager.api.*');
    }

    public function wrapResponse(JsonResponse $response): JsonResponse
    {
        $statusCode = $response->getStatusCode();
        $isSuccess  = $statusCode >= 200 && $statusCode < 300;
        $content    = $response->getData(true);

        if ($statusCode === Response::HTTP_NO_CONTENT) {
            return new JsonResponse([], $statusCode);
        }

        $message = Response::$statusTexts[$statusCode] ?? trans('storage-manager::storage-manager.error.unknown');

        if ($statusCode === Response::HTTP_UNAUTHORIZED) {
            Arr::forget($content, 'message');
        }

        $wrapped = [
            'timestamp' => now()->toIso8601String(),
            'status'    => $statusCode,
            'message'   => $message,
        ];

        if ($isSuccess) {
            $wrapped['data'] = $content;
        } else {
            $wrapped['errors'] = $content['errors'] ?? $content;
        }

        return new JsonResponse($wrapped, $statusCode);
    }
}
