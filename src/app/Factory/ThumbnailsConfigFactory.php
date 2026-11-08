<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Factory;

use Illuminate\Support\Arr;
use Kwaadpepper\LaravelStorageManager\Config\ThumbnailsConfig;

class ThumbnailsConfigFactory
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function build(array $config): ThumbnailsConfig
    {
        $enabled       = Arr::boolean($config, 'thumbnails.enabled', true);
        $cacheDuration = Arr::integer($config, 'thumbnails.cache_duration', 604800);
        $width         = Arr::integer($config, 'thumbnails.width', 160);
        $height        = Arr::integer($config, 'thumbnails.height', 160);
        $quality       = Arr::integer($config, 'thumbnails.quality', 80);

        return new ThumbnailsConfig(
            $enabled,
            $cacheDuration >= 0 ? $cacheDuration : 604800,
            $width  > 0 ? $width : 160,
            $height > 0 ? $height : 160,
            $quality >= 1 && $quality <= 100 ? $quality : 80
        );
    }
}
