<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Exception;

use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthenticationException extends HttpException implements ShouldntReport
{
    public function __construct()
    {
        parent::__construct(
            Response::HTTP_UNAUTHORIZED,
            Response::$statusTexts[Response::HTTP_UNAUTHORIZED] ?? 'Unauthorized',
            null,
            [],
            0
        );
    }
}
