<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Factory;

use Illuminate\Support\Arr;
use Kwaadpepper\LaravelStorageManager\Config\RouteConfig;

class RouteConfigFactory
{
    private const string DEFAULT_PREFIX = 'sm';

    /**
     * @param  array<string, mixed>  $config
     */
    public function build(array $config): RouteConfig
    {
        $rawPrefix = Arr::get($config, 'route.prefix', self::DEFAULT_PREFIX);
        $prefix    = is_string($rawPrefix) && trim($rawPrefix) !== '' ? $rawPrefix : self::DEFAULT_PREFIX;

        $rawMiddleware = Arr::get($config, 'route.middleware', []);
        if (is_string($rawMiddleware)) {
            $rawMiddleware = explode(',', $rawMiddleware);
        }
        if (! is_array($rawMiddleware)) {
            $rawMiddleware = [];
        }

        /** @var list<string> $routeMiddleware */
        $routeMiddleware = array_values(array_filter(
            $rawMiddleware,
            static fn (mixed $item): bool => is_string($item) && ! empty(mb_trim($item))
        ));

        return new RouteConfig($prefix, $routeMiddleware);
    }
}
