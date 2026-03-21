<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Service;

/**
 * Is used in order to determine if a user can
 * have access to the file manager
 */
class AuthService
{
    public function check(): bool
    {
        // FIXME: Implement your own authentication logic here.
        // For now, we allow all access. You can implement your own logic here.
        return true;
    }
}
