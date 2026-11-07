<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Factory;

use Closure;
use Illuminate\Support\Arr;
use Kwaadpepper\LaravelStorageManager\Config\FrontendConfig;

class FrontendConfigFactory
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function build(array $config): FrontendConfig
    {
        $devServerEnabled = Arr::boolean($config, 'frontend.dev_server.enabled', false);

        $devUrl       = Arr::get($config, 'frontend.dev_server.url', 'http://localhost:5173');
        $devServerUrl = is_string($devUrl) && $devUrl !== '' ? rtrim($devUrl, '/') : 'http://localhost:5173';

        $rawNonce = Arr::get($config, 'frontend.csp_nonce');
        $cspNonce = ($rawNonce instanceof Closure || (is_string($rawNonce) && $rawNonce !== '')) ? $rawNonce : null;

        return new FrontendConfig($devServerEnabled, $devServerUrl, $cspNonce);
    }
}
