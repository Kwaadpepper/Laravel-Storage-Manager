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
                        readOnly: (bool) Arr::get($fsConfig, "{$diskName}.read-only", false),
                    ),
                ]
            )
            ->toBase()
            ->all();
    }

    public function getStaticConfig(string $key): string | int
    {
        $value = $this->getConfigValueFrom($key, $this->staticConfig);

        if (! is_string($value) && ! is_int($value)) {
            throw new \UnexpectedValueException(
                "Invalid static config value for key '{$key}'. Expected string or int."
            );
        }

        return $value;
    }

    /**
     * @param  string|int|float|bool|array<mixed,mixed>|null  $default
     * @return string|int|float|bool|array<mixed,mixed>|null
     */
    public function getConfig(
        string $key,
        string | int | float | bool | array | null $default = null
    ): string | int | float | bool | array | null {
        return $this->getConfigValueFrom(
            $key,
            $this->config
        ) ?? $default;
    }

    /**
     * @param  array<string,mixed>  $from
     * @return string|int|float|bool|array<mixed,mixed>|null
     */
    private function getConfigValueFrom(
        string $key,
        array $from
    ): string | int | float | bool | array | null {
        $value = Arr::get($from, $key);

        if (
            is_string($value) || is_int($value) || is_float($value) || is_bool($value) || is_array($value) || $value === null
        ) {
            return $value;
        }

        throw new \UnexpectedValueException(
            "Invalid config value for key '{$key}'. Expected string, int, float, bool, array or null."
        );
    }
}
