<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Service;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Kwaadpepper\LaravelStorageManager\Repository\ConfigRepository;

/**
 * Is used in order to determine if a user can
 * have access to the file manager
 */
class AuthService
{
    public function __construct(
        private readonly ConfigRepository $configRepository
    ) {
    }

    public function check(): bool
    {
        if (! $this->configRepository->isAuthEnabled()) {
            return true;
        }

        $routeGuard = $this->getGuardFromCurrentRoute();

        if ($routeGuard) {
            return Auth::guard($routeGuard)->check();
        }

        return Auth::check();
    }

    public function user(): ?Authenticatable
    {
        $routeGuard = $this->getGuardFromCurrentRoute();

        if ($routeGuard && Auth::guard($routeGuard)->check()) {
            return Auth::guard($routeGuard)->user();
        }

        return Auth::user();
    }

    private function getGuardFromCurrentRoute(): ?string
    {
        $route = Request::route();

        if (! $route || ! method_exists($route, 'gatherMiddleware')) {
            return null;
        }

        $middlewares = $route->gatherMiddleware();

        foreach ($middlewares as $middleware) {
            if (is_string($middleware) && Str::startsWith($middleware, 'auth:')) {
                return Str::after($middleware, 'auth:');
            }
        }

        return null;
    }
}
