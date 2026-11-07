<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Service;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;

/**
 * Is used in order to determine if a user can
 * have access to the file manager
 */
class AuthService
{
    public function __construct(
        private readonly StorageManagerConfig $config,
        private readonly AuthFactory $authFactory,
        private readonly Request $request,
    ) {
    }

    public function check(): bool
    {
        if (! $this->config->auth->enabled) {
            return true;
        }

        $routeGuard = $this->getGuardFromCurrentRoute();

        if ($routeGuard) {
            return $this->authFactory->guard($routeGuard)->check();
        }

        return $this->authFactory->guard()->check();
    }

    public function user(): ?Authenticatable
    {
        $routeGuard = $this->getGuardFromCurrentRoute();

        if ($routeGuard && $this->authFactory->guard($routeGuard)->check()) {
            return $this->authFactory->guard($routeGuard)->user();
        }

        return $this->authFactory->guard()->user();
    }

    private function getGuardFromCurrentRoute(): ?string
    {
        $route = $this->request->route();

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
