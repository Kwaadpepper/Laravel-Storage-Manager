<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Factory;

use Illuminate\Support\Arr;
use Kwaadpepper\LaravelStorageManager\Config\AuthConfig;

class AuthConfigFactory
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function build(array $config): AuthConfig
    {
        $enabled        = Arr::boolean($config, 'auth.enabled', true);
        $guard          = Arr::get($config, 'auth.guard', 'web');
        $guard          = is_string($guard) && $guard !== '' ? $guard : 'web';
        $allowByDefault = Arr::boolean($config, 'auth.allow_by_default', false);

        return new AuthConfig($enabled, $guard, $allowByDefault);
    }
}
