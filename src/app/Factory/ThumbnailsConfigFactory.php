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
        $enabled = Arr::boolean($config, 'thumbnails.enabled', true);

        $cacheDuration = Arr::get($config, 'thumbnails.cache_duration', 604800);
        $cacheDuration = is_int($cacheDuration) && $cacheDuration >= 0 ? $cacheDuration : 604800;

        $width = Arr::get($config, 'thumbnails.width', 160);
        $width = is_int($width) && $width > 0 ? $width : 160;

        $height = Arr::get($config, 'thumbnails.height', 160);
        $height = is_int($height) && $height > 0 ? $height : 160;

        $quality = Arr::get($config, 'thumbnails.quality', 80);
        $quality = is_int($quality) && $quality >= 1 && $quality <= 100 ? $quality : 80;

        return new ThumbnailsConfig($enabled, $cacheDuration, $width, $height, $quality);
    }
}
