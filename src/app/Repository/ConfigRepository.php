<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Repository;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;

class ConfigRepository
{
    private const DEFAULT_PREFIX = 'sm';

    /**
     * @param  array<string,mixed>  $config
     * @param  array<string,mixed>  $staticConfig
     */
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
        $guard = $this->getConfig('auth.guard', 'web');

        return is_string($guard) && $guard !== '' ? $guard : 'web';
    }

    public function getRoutePrefix(): string
    {
        $prefix = $this->getConfig('route.prefix', self::DEFAULT_PREFIX);

        return is_string($prefix) && $prefix !== '' ? $prefix : self::DEFAULT_PREFIX;
    }

    /**
     * @return list<string>
     */
    public function getRouteMiddleware(): array
    {
        $middleware = Arr::wrap($this->getConfig('route.middleware', []));

        return array_values(array_filter(
            $middleware,
            static fn (mixed $item): bool => is_string($item) && ! empty(mb_trim($item))
        ));
    }

    public function getDefaultDisk(): ?Disk
    {
        $disksConfigRaw = Config::get('filesystems.disks', []);
        $disksConfig    = is_array($disksConfigRaw) ? $disksConfigRaw : [];

        /** @var int|string|null $fallbackDefaultDiskName */
        $fallbackDefaultDiskName = array_key_first($disksConfig);

        $defaultDiskName = $this->getConfig('filesystems.default', $fallbackDefaultDiskName);

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
        $fsConfig          = Arr::wrap(Config::get('filesystems.disks', []));
        $disksAvailableRaw = Arr::wrap($this->getConfig('disks.available', []));

        /** @var list<string> $disksAvailable */
        $disksAvailable = array_values(array_filter(
            $disksAvailableRaw,
            static fn (mixed $disk): bool => is_string($disk) && $disk !== ''
        ));

        return collect($disksAvailable)
            ->filter(fn (string $diskName): bool => Arr::has($fsConfig, $diskName))
            ->mapWithKeys(
                fn (string $diskName): array => [
                    $diskName => new Disk(
                        driver: is_string($driver = Arr::get($fsConfig, "{$diskName}.driver")) ? $driver : '',
                        name: $diskName,
                        throw: (bool) Arr::get($fsConfig, "{$diskName}.throw", false),
                        report: (bool) Arr::get($fsConfig, "{$diskName}.report", false),
                    ),
                ]
            )
            ->toBase()
            ->all();
    }

    public function getStaticConfig(string $key): mixed
    {
        return Arr::get($this->staticConfig, $key);
    }

    /**
     * @param  string|int|float|bool|array<mixed>|null  $default
     */
    private function getConfig(
        string $key,
        string | int | float | bool | array | null $default = null
    ): mixed {
        return Arr::get($this->config, $key, $default);
    }
}
