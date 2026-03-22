<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Exception;

use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FileOperationException extends HttpException implements ShouldntReport
{
    public function __construct()
    {
        parent::__construct(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'File operation failed',
            null,
            [],
            0
        );
    }
}
