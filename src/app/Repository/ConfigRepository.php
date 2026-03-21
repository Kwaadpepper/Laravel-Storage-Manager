<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Repository;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;

class ConfigRepository
{
    private const DEFAULT_PREFIX = 'sm';

    public function __construct(
        private array $config,
        private array $staticConfig
    ) {
    }

    public function isEnabled(): bool
    {
        return (bool) $this->getConfig('enabled', true);
    }

    public function isAuthEnabled(): bool
    {
        return (bool) $this->getConfig('auth.enabled', true);
    }

    public function getAuthGuard(): string
    {
        return (string) $this->getConfig('auth.guard', 'web');
    }

    public function getRoutePrefix(): string
    {
        $prefix = $this->getConfig(
            'route.prefix',
            self::DEFAULT_PREFIX
        );

        if (! is_string($prefix) || empty(mb_trim($prefix))) {
            return self::DEFAULT_PREFIX;
        }

        return $prefix;
    }

    public function getRouteMiddleware(): array
    {
        $middleware = $this->getConfig('route.middleware', []);

        if (! is_array($middleware)) {
            return [];
        }

        return array_filter(
            $middleware,
            fn (mixed $item): bool => is_string($item) && ! empty(mb_trim($item))
        );
    }

    public function getDefaultDisk(): ?Disk
    {
        $defaultDiskName = $this->getConfig(
            'filesystems.default',
            Config::get('filesystems.disks', []) ?
              array_key_first(Config::get('filesystems.disks', [])) : null
        );

        if (! is_string($defaultDiskName) || empty(mb_trim($defaultDiskName))) {
            return null;
        }

        $disksMap = $this->getDisksMap();

        if (isset($disksMap[$defaultDiskName])) {
            return $disksMap[$defaultDiskName];
        }

        return array_values($disksMap)[0] ?? null;
    }

    /**
     * @return array<string,Disk>
     */
    public function getDisksMap(): array
    {
        $fsConfig = Arr::wrap(Config::get('filesystems.disks', []));

        return collect($this->getConfig('disks.available', []))
            ->map(fn (string $diskName): array => Arr::has($fsConfig, $diskName) ? [
                'name'   => $diskName,
                'driver' => Arr::get($fsConfig, "{$diskName}.driver"),
                'throw'  => Arr::get($fsConfig, "{$diskName}.throw", false),
                'report' => Arr::get($fsConfig, "{$diskName}.report", false),
            ] : null)
            ->filter()
            ->mapWithKeys(
                fn (array $diskConfig) => [
                    $diskConfig['name'] => $this->mapConfigDisk(
                        name: $diskConfig['name'],
                        diskConfig: $diskConfig
                    ),
                ]
            )->toArray();
    }

    private function mapConfigDisk(string $name, array $diskConfig): Disk
    {
        return new Disk(
            driver: $diskConfig['driver'],
            name: $name,
            throw: $diskConfig['throw']   ?? false,
            report: $diskConfig['report'] ?? false
        );
    }

    public function getStaticConfig(string $key): mixed
    {
        return Arr::get($this->staticConfig, $key);
    }

    private function getConfig(
        string $key,
        string | int | float | bool | array | null $default = null
    ): mixed {
        return Arr::get($this->config, $key, $default);
    }
}
