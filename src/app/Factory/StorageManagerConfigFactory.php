<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Factory;

use Illuminate\Support\Arr;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;

class StorageManagerConfigFactory
{
    public function __construct(
        private readonly AuthConfigFactory $authConfigFactory,
        private readonly RouteConfigFactory $routeConfigFactory,
        private readonly UploadConfigFactory $uploadConfigFactory,
        private readonly DisksConfigFactory $disksConfigFactory,
        private readonly ThumbnailsConfigFactory $thumbnailsConfigFactory,
        private readonly StatsConfigFactory $statsConfigFactory,
        private readonly TrashConfigFactory $trashConfigFactory,
        private readonly FrontendConfigFactory $frontendConfigFactory
    ) {
    }

    public static function make(): self
    {
        return new self(
            new AuthConfigFactory(),
            new RouteConfigFactory(),
            new UploadConfigFactory(),
            new DisksConfigFactory(),
            new ThumbnailsConfigFactory(),
            new StatsConfigFactory(),
            new TrashConfigFactory(),
            new FrontendConfigFactory()
        );
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $staticConfig
     * @param  array<string, array<string, scalar|null>>  $filesystemsDisks
     */
    public function create(array $config, array $staticConfig = [], array $filesystemsDisks = []): StorageManagerConfig
    {
        $enabled    = Arr::boolean($config, 'enabled', true);
        $auth       = $this->authConfigFactory->build($config);
        $route      = $this->routeConfigFactory->build($config);
        $upload     = $this->uploadConfigFactory->build($config);
        $disks      = $this->disksConfigFactory->build($config, $filesystemsDisks);
        $thumbnails = $this->thumbnailsConfigFactory->build($config);
        $stats      = $this->statsConfigFactory->build($config);
        $trash      = $this->trashConfigFactory->build($config);
        $frontend   = $this->frontendConfigFactory->build($config);

        $validStatic = [];
        foreach ($staticConfig as $key => $val) {
            if (is_string($key) && (is_string($val) || is_int($val))) {
                $validStatic[$key] = $val;
            }
        }

        return new StorageManagerConfig(
            $enabled,
            $auth,
            $route,
            $upload,
            $disks,
            $thumbnails,
            $stats,
            $trash,
            $frontend,
            $validStatic
        );
    }
}
