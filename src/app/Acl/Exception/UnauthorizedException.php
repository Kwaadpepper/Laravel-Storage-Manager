<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Acl\Exception;

use Illuminate\Auth\Access\AuthorizationException;

class UnauthorizedException extends AuthorizationException
{
}
