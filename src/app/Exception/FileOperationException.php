<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Exception;

use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\Response;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FileOperationException extends HttpException implements ShouldntReport
{
    public function __construct(
        FileOperationError $error
    ) {
        parent::__construct(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $error->message(),
            null,
            [],
            $error->httpStatusCode()
        );
    }

    public static function throwWith(FileOperationError $error): never
    {
        throw new self($error);
    }
}
