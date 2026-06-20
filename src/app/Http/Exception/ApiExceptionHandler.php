<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Exception;

use Illuminate\Validation\ValidationException;
use Kwaadpepper\LaravelStorageManager\Exception\DomainException;
use Kwaadpepper\LaravelStorageManager\Http\Dto\DomainErrorDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\ErrorDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\FieldErrorsDto;
use Kwaadpepper\LaravelStorageManager\Http\Response\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;

final class ApiExceptionHandler
{
    public function toApiResponse(Throwable $exception): ApiResponse
    {
        if ($exception instanceof ValidationException) {
            return ApiResponse::json(
                new FieldErrorsDto($this->normalizeValidationErrors($exception)),
                ApiResponse::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        if ($exception instanceof DomainException) {
            $domainErrorCode = $exception->getDomainErrorCode();

            return ApiResponse::json(
                new DomainErrorDto(
                    $domainErrorCode->code(),
                    $exception->getMessage()
                ),
                ApiResponse::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        return ApiResponse::json(
            new ErrorDto($exception->getMessage()),
            ApiResponse::HTTP_INTERNAL_SERVER_ERROR
        );
    }

    public function toApiResponseFromRenderedJsonResponse(JsonResponse $response): ?ApiResponse
    {
        $content = json_decode($response->getContent() ?: '[]', true);
        if (! is_array($content)) {
            return null;
        }

        $exceptionClass = $content['exception'] ?? null;
        $apiResponse    = null;

        if (is_string($exceptionClass) && is_a($exceptionClass, DomainException::class, true)) {
            $apiResponse = ApiResponse::json(
                new DomainErrorDto(
                    $this->normalizeRenderedDomainErrorCode($content),
                    $this->normalizeRenderedDomainErrorMessage($content)
                ),
                ApiResponse::HTTP_UNPROCESSABLE_ENTITY
            );
        } elseif (
            (is_string($exceptionClass) && is_a($exceptionClass, ValidationException::class, true))
            || isset($content['errors'])
        ) {
            $apiResponse = ApiResponse::json(
                new FieldErrorsDto($this->normalizeRenderedValidationErrors($content['errors'] ?? [])),
                ApiResponse::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        return $apiResponse;
    }

    /**
     * @return array<string, string>
     */
    private function normalizeValidationErrors(ValidationException $exception): array
    {
        $errors = [];

        foreach ($exception->errors() as $field => $messages) {
            $firstMessage   = $messages[0] ?? trans('storage-manager::storage-manager.error.unknown');
            $errors[$field] = is_string($firstMessage)
                ? $firstMessage
                : trans('storage-manager::storage-manager.error.unknown');
        }

        return $errors;
    }

    /**
     * @return array<string, string>
     */
    private function normalizeRenderedValidationErrors(mixed $errors): array
    {
        if (! is_array($errors)) {
            return [];
        }

        $normalized = [];
        foreach ($errors as $field => $messages) {
            $fieldName = is_string($field) ? $field : strval($field);

            if (is_array($messages)) {
                $firstMessage           = $messages[0] ?? trans('storage-manager::storage-manager.error.unknown');
                $normalized[$fieldName] = is_string($firstMessage)
                    ? $firstMessage
                    : trans('storage-manager::storage-manager.error.unknown');

                continue;
            }

            $normalized[$fieldName] = is_string($messages)
                ? $messages
                : trans('storage-manager::storage-manager.error.unknown');
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function normalizeRenderedDomainErrorCode(array $content): int
    {
        $errors = $content['errors'] ?? [];
        if (! is_array($errors)) {
            return 0;
        }

        $code = $errors['code'] ?? 0;

        return is_int($code) ? $code : 0;
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function normalizeRenderedDomainErrorMessage(array $content): string
    {
        $message = $content['message'] ?? trans('storage-manager::storage-manager.error.unknown');

        return is_string($message) ? $message : trans('storage-manager::storage-manager.error.unknown');
    }
}
