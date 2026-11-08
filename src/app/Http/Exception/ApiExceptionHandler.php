<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Exception;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;
use Kwaadpepper\LaravelStorageManager\Enum\GenericDomainError;
use Kwaadpepper\LaravelStorageManager\Exception\DomainException;
use Kwaadpepper\LaravelStorageManager\Exception\OperationCancelledException;
use Kwaadpepper\LaravelStorageManager\Exception\OperationNotSupportedException;
use Kwaadpepper\LaravelStorageManager\Exception\StorageConnectionException;
use Kwaadpepper\LaravelStorageManager\Http\Dto\DomainErrorDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\ErrorDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\FieldErrorsDto;
use Kwaadpepper\LaravelStorageManager\Http\Response\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ApiExceptionHandler
{
    public function toApiResponse(\Throwable $exception): ApiResponse
    {
        if ($exception instanceof AuthorizationException) {
            return ApiResponse::json(
                new ErrorDto($exception->getMessage() ?: 'This action is unauthorized.'),
                ApiResponse::HTTP_FORBIDDEN
            );
        }

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
        /** @var array<string, mixed>|mixed $content */
        $content = json_decode($response->getContent() ?: '[]', true);
        if (! is_array($content)) {
            return null;
        }
        /** @var array<string, mixed> $content */
        $exceptionClass = $content['exception'] ?? null;
        $apiResponse    = null;

        if (is_string($exceptionClass) && is_a($exceptionClass, AuthorizationException::class, true)) {
            $message = $content['message'] ?? 'This action is unauthorized.';

            return ApiResponse::json(
                new ErrorDto(is_string($message) ? $message : 'This action is unauthorized.'),
                ApiResponse::HTTP_FORBIDDEN
            );
        }

        if (is_string($exceptionClass) && is_a($exceptionClass, DomainException::class, true)) {
            $apiResponse = ApiResponse::json(
                new DomainErrorDto(
                    $this->normalizeRenderedDomainErrorCode($content, $exceptionClass),
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
        /** @var array<string, string> $errors */
        $errors = [];

        /** @var array<string, list<string>> $rawErrors */
        $rawErrors = $exception->errors();

        foreach ($rawErrors as $field => $messages) {
            $fallback       = (string) trans('storage-manager::storage-manager.error.unknown');
            $firstMessage   = $messages[0] ?? $fallback;
            $errors[$field] = is_string($firstMessage) ? $firstMessage : $fallback;
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
    private function normalizeRenderedDomainErrorCode(array $content, ?string $exceptionClass = null): int
    {
        $code = $this->resolveCodeFromContent($content)
            ?? $this->resolveCodeFromExceptionClass($exceptionClass);

        if ($code !== null) {
            return $code;
        }

        return $this->resolveCodeFromMessage($this->normalizeRenderedDomainErrorMessage($content));
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function resolveCodeFromContent(array $content): ?int
    {
        $errors = $content['errors'] ?? [];
        if (is_array($errors) && isset($errors['code']) && is_int($errors['code']) && $errors['code'] > 0) {
            return $errors['code'];
        }

        $code = $content['code'] ?? null;
        if (is_int($code) && $code > 0) {
            return $code;
        }

        return null;
    }

    private function resolveCodeFromExceptionClass(?string $exceptionClass): ?int
    {
        if ($exceptionClass === null) {
            return null;
        }

        return match (true) {
            is_a($exceptionClass, OperationCancelledException::class, true)    => FileOperationError::OPERATION_CANCELLED->code(),
            is_a($exceptionClass, StorageConnectionException::class, true)     => FileOperationError::CONNECTION_ERROR->code(),
            is_a($exceptionClass, OperationNotSupportedException::class, true) => FileOperationError::OPERATION_NOT_SUPPORTED->code(),
            default                                                            => null,
        };
    }

    private function resolveCodeFromMessage(string $message): int
    {
        foreach (FileOperationError::cases() as $case) {
            if ($case->message() === $message) {
                return $case->code();
            }
        }

        foreach (GenericDomainError::cases() as $case) {
            if ($case->message() === $message) {
                return $case->code();
            }
        }

        return 0;
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
