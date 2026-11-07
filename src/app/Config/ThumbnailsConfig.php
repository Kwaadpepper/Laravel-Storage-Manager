<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Config;

final readonly class ThumbnailsConfig
{
    public function __construct(
        public bool $thumbsAreEnabled,
        public int $cacheDuration,
        public int $width,
        public int $height,
        public int $quality
    ) {
    }
}
