<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Config;

use Kwaadpepper\LaravelStorageManager\Factory\StorageManagerConfigFactory;

final readonly class StorageManagerConfig
{
    public const array UNSUPPORTED_EXTENSIONS = UploadConfig::UNSUPPORTED_EXTENSIONS;

    /**
     * @param  array<string, string|int>  $staticConfig
     */
    public function __construct(
        public bool $isStorageManagerEnabled,
        public AuthConfig $auth,
        public RouteConfig $route,
        public UploadConfig $upload,
        public DisksConfig $disks,
        public ThumbnailsConfig $thumbnails,
        public StatsConfig $stats,
        public TrashConfig $trash,
        public FrontendConfig $frontend,
        public array $staticConfig = []
    ) {
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $staticConfig
     * @param  array<string, array<string, scalar|null>>  $filesystemsDisks
     */
    public static function fromArray(array $config, array $staticConfig = [], array $filesystemsDisks = []): self
    {
        return StorageManagerConfigFactory::make()->create($config, $staticConfig, $filesystemsDisks);
    }

    public function getStaticConfig(string $key): string | int
    {
        if (! array_key_exists($key, $this->staticConfig)) {
            throw new \UnexpectedValueException(
                "Invalid static config value for key '{$key}'. Expected string or int."
            );
        }

        return $this->staticConfig[$key];
    }
}
