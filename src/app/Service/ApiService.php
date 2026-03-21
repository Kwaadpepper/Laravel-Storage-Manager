<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Service;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Kwaadpepper\LaravelStorageManager\Repository\ConfigRepository;
use Symfony\Component\HttpFoundation\Response;

class ApiService
{
    public function __construct(
        private readonly AuthService $authService,
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

        return $request->expectsJson() && $request->routeIs('storage-manager.api.*');
    }

    public function checkAuth(): bool
    {
        return $this->authService->check();
    }

    public function wrapResponse(Response $response): Response
    {
        if (! $response instanceof JsonResponse) {
            return $response;
        }

        $statusCode = $response->getStatusCode();
        $isSuccess  = $statusCode >= 200 && $statusCode < 300;
        $content    = $response->getData(true);

        $wrapped = [
            'timestamp' => now()->toIso8601String(),
            'status'    => $statusCode,
            'message'   => Response::$statusTexts[$statusCode] ?? trans('storage-manager::storage-manager.error.unknown'),
        ];

        if ($isSuccess) {
            $wrapped['data'] = $content;
        } else {
            $wrapped['errors'] = $content['errors'] ?? $content;
        }

        return new JsonResponse($wrapped, $statusCode);
    }
}
