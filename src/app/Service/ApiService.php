<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Service;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Http\Dto\Dto;
use Kwaadpepper\LaravelStorageManager\Http\Response\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

class ApiService
{
    public function __construct(
        private readonly StorageManagerConfig $config
    ) {
    }

    public function isAllowedToRequest(Request $request): bool
    {
        if (! $this->config->isStorageManagerEnabled) {
            return false;
        }

        return $request->routeIs('storage-manager.*');
    }

    public function isAllowedToRequestApi(Request $request): bool
    {
        if (! $this->config->isStorageManagerEnabled) {
            return false;
        }

        return $this->isApiRequest($request);
    }

    public function isApiRequest(Request $request): bool
    {
        return ($request->expectsJson() || $request->accepts('text/event-stream'))
            && $request->routeIs('storage-manager.api.*');
    }

    public function wrapResponse(JsonResponse $response): ApiResponse
    {
        if ($this->isWrappedResponse($response)) {
            /** @var ApiResponse $response */
            return $response;
        }

        $statusCode = $response->getStatusCode();
        $decoded    = json_decode($response->getContent() ?: '[]', true) ?: [];
        /** @var array<string, mixed> $content */
        $content    = Arr::wrap($decoded);

        $dto = new class ($content) implements Dto
        {
            /** @param  array<string,mixed>  $content */
            public function __construct(
                private readonly array $content
            ) {
            }

            public function jsonSerialize(): mixed
            {
                return $this->content;
            }
        };

        return new ApiResponse($dto, $statusCode);
    }

    public function isWrappedResponse(JsonResponse $response): bool
    {
        return $response instanceof ApiResponse;
    }
}
