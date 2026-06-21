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
        return Arr::boolean($this->config, 'enabled', true);
    }

    public function isAuthEnabled(): bool
    {
        return Arr::boolean($this->config, 'auth.enabled', true);
    }

    public function getAuthGuard(): string
    {
        $guard = Arr::get($this->config, 'auth.guard', 'web');

        return is_string($guard) && $guard !== '' ? $guard : 'web';
    }

    public function getRoutePrefix(): string
    {
        $prefix = Arr::get($this->config, 'route.prefix', self::DEFAULT_PREFIX);

        return is_string($prefix) && $prefix !== '' ? $prefix : self::DEFAULT_PREFIX;
    }

    public function getUploadChunkSize(): int
    {
        $size = Arr::get($this->config, 'upload.chunk_size', 2 * 1024 * 1024);

        return is_int($size) ? $size : 2 * 1024 * 1024;
    }

    public function getUploadTempDisk(): ?string
    {
        $disk = Arr::get($this->config, 'upload.temp_disk');

        return is_string($disk) && $disk !== '' ? $disk : null;
    }

    public function getUploadTempPath(): string
    {
        $path = Arr::get($this->config, 'upload.temp_path', 'lsm_uploads');

        return is_string($path) && $path !== '' ? $path : 'lsm_uploads';
    }

    /**
     * @return list<string>
     */
    public function getRouteMiddleware(): array
    {
        $middleware = Arr::array($this->config, 'route.middleware', []);

        return array_values(array_filter(
            $middleware,
            static fn (mixed $item): bool => is_string($item) && ! empty(mb_trim($item))
        ));
    }

    public function getDefaultDisk(): ?Disk
    {
        $disksConfig = Config::array('filesystems.disks', []);

        /** @var int|string|null $fallbackDefaultDiskName */
        $fallbackDefaultDiskName = array_key_first($disksConfig);

        $defaultDiskName = Arr::get($this->config, 'filesystems.default', $fallbackDefaultDiskName);

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
        $fsConfig          = Config::array('filesystems.disks', []);
        $disksAvailableRaw = Arr::array($this->config, 'disks.available', []);

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
                        throw: Arr::boolean($fsConfig, "{$diskName}.throw", false),
                        report: Arr::boolean($fsConfig, "{$diskName}.report", false),
                        readOnly: Arr::boolean($fsConfig, "{$diskName}.read-only", false),
                    ),
                ]
            )
            ->toBase()
            ->all();
    }

    public function getStaticConfig(string $key): string | int
    {
        $value = Arr::get($this->staticConfig, $key);

        if (! is_string($value) && ! is_int($value)) {
            throw new \UnexpectedValueException(
                "Invalid static config value for key '{$key}'. Expected string or int."
            );
        }

        return $value;
    }
}
