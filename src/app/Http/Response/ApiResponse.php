<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Response;

use Illuminate\Http\JsonResponse;
use Kwaadpepper\LaravelStorageManager\Http\Dto\Dto;
use Symfony\Component\HttpFoundation\Response;

final class ApiResponse extends JsonResponse
{
    public function __construct(
        Dto $data,
        int $status = 200,
    ) {
        parent::__construct(
            $this->wrapData($data, $status),
            $status
        );
    }

    public static function json(Dto $data, int $status = 200): self
    {
        return new self($data, $status);
    }

    /**
     * @return array<string,mixed>
     */
    private function wrapData(Dto $data, int $statusCode): array
    {
        $isSuccess  = $statusCode >= 200 && $statusCode < 300;

        if ($statusCode === ApiResponse::HTTP_NO_CONTENT) {
            return [];
        }

        $message = Response::$statusTexts[$statusCode] ?? trans('storage-manager::storage-manager.error.unknown');

        $wrapped = [
            'timestamp' => now()->toIso8601String(),
            'status'    => $statusCode,
            'message'   => $message,
        ];

        if ($isSuccess) {
            $wrapped['data'] = $data;
        } else {
            $wrapped['errors'] = $data;
        }

        return $wrapped;
    }
}
