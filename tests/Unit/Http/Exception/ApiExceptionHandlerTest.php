<?php

declare(strict_types=1);

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Exception\OperationCancelledException;
use Kwaadpepper\LaravelStorageManager\Exception\OperationNotSupportedException;
use Kwaadpepper\LaravelStorageManager\Exception\StorageConnectionException;
use Kwaadpepper\LaravelStorageManager\Http\Exception\ApiExceptionHandler;
use Symfony\Component\HttpFoundation\JsonResponse;

describe('ApiExceptionHandler', function (): void {
    it('converts exceptions to ApiResponse', function (): void {
        // GIVEN
        $handler   = new ApiExceptionHandler();
        $authEx    = new AuthorizationException('Forbidden action.');
        $valEx     = ValidationException::withMessages(['field' => ['Invalid value']]);
        $domainEx  = new FileOperationException(FileOperationError::FILE_NOT_FOUND);
        $genericEx = new \RuntimeException('Internal failure');

        // WHEN
        $authResponse    = $handler->toApiResponse($authEx);
        $valResponse     = $handler->toApiResponse($valEx);
        $domainResponse  = $handler->toApiResponse($domainEx);
        $genericResponse = $handler->toApiResponse($genericEx);

        // THEN
        expect($authResponse->getStatusCode())->toBe(403);
        expect($valResponse->getStatusCode())->toBe(422);
        expect($domainResponse->getStatusCode())->toBe(422);
        expect($genericResponse->getStatusCode())->toBe(500);
    });

    it('converts rendered json response to ApiResponse', function (): void {
        // GIVEN
        $handler          = new ApiExceptionHandler();
        $nonArrayResponse = new JsonResponse('"string-only"');
        $authResponse     = new JsonResponse([
            'exception' => AuthorizationException::class,
            'message'   => 'Action unauthorized',
        ], 403);
        $domainWithCodeResponse = new JsonResponse([
            'exception' => FileOperationException::class,
            'message'   => FileOperationError::FILE_NOT_FOUND->message(),
            'errors'    => ['code' => FileOperationError::FILE_NOT_FOUND->code()],
        ], 422);
        $domainWithClassResponse = new JsonResponse([
            'exception' => StorageConnectionException::class,
            'message'   => 'Cannot connect',
        ], 422);
        $domainCancelledResponse = new JsonResponse([
            'exception' => OperationCancelledException::class,
            'message'   => 'Cancelled',
        ], 422);
        $domainNotSupportedResponse = new JsonResponse([
            'exception' => OperationNotSupportedException::class,
            'message'   => 'Not supported',
        ], 422);
        $validationResponse = new JsonResponse([
            'exception' => ValidationException::class,
            'errors'    => ['name' => ['Required field'], 'other' => 'Flat error string'],
        ], 422);

        // WHEN
        $nullResult         = $handler->toApiResponseFromRenderedJsonResponse($nonArrayResponse);
        $authResult         = $handler->toApiResponseFromRenderedJsonResponse($authResponse);
        $domainResult       = $handler->toApiResponseFromRenderedJsonResponse($domainWithCodeResponse);
        $connectionResult   = $handler->toApiResponseFromRenderedJsonResponse($domainWithClassResponse);
        $cancelledResult    = $handler->toApiResponseFromRenderedJsonResponse($domainCancelledResponse);
        $notSupportedResult = $handler->toApiResponseFromRenderedJsonResponse($domainNotSupportedResponse);
        $validationResult   = $handler->toApiResponseFromRenderedJsonResponse($validationResponse);

        // THEN
        expect($nullResult)->toBeNull();
        expect($authResult?->getStatusCode())->toBe(403);
        expect($domainResult?->getStatusCode())->toBe(422);
        expect($connectionResult?->getStatusCode())->toBe(422);
        expect($cancelledResult?->getStatusCode())->toBe(422);
        expect($notSupportedResult?->getStatusCode())->toBe(422);
        expect($validationResult?->getStatusCode())->toBe(422);
    });
})->covers(ApiExceptionHandler::class);
