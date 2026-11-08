<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Factory;

use Illuminate\Support\Arr;
use Kwaadpepper\LaravelStorageManager\Config\DisksConfig;
use Kwaadpepper\LaravelStorageManager\Enum\DuplicatePolicy;
use Kwaadpepper\LaravelStorageManager\Enum\FilenamePolicy;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;

class DisksConfigFactory
{
    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, array<string, scalar|null>>  $filesystemsDisks
     */
    public function build(array $config, array $filesystemsDisks = []): DisksConfig
    {
        $disksAvailableRaw = Arr::get($config, 'disks.available', []);
        if (is_string($disksAvailableRaw)) {
            $disksAvailableRaw = explode(',', $disksAvailableRaw);
        }
        if (! is_array($disksAvailableRaw)) {
            $disksAvailableRaw = [];
        }

        /** @var list<string> $availableDisks */
        $availableDisks = array_values(array_filter(
            $disksAvailableRaw,
            static fn (mixed $disk): bool => is_string($disk) && ! empty(mb_trim($disk))
        ));

        $rawDefault = Arr::get($config, 'filesystems.default', Arr::get($config, 'disks.default'));
        $default    = is_string($rawDefault) && ! empty(mb_trim($rawDefault)) ? mb_trim($rawDefault) : null;

        $disks = $this->buildDisks($config, $availableDisks, $filesystemsDisks);

        return new DisksConfig($availableDisks, $default, $filesystemsDisks, $disks);
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  list<string>  $availableDisks
     * @param  array<string, array<string, scalar|null>>  $filesystemsDisks
     * @return array<string, Disk>
     */
    private function buildDisks(array $config, array $availableDisks, array $filesystemsDisks): array
    {
        $rawConfigs               = Arr::get($config, 'disks.configs', []);
        $globalTrashEnabled       = Arr::boolean($config, 'trash.enabled', true);
        $globalTrashFolder        = Arr::string($config, 'trash.folder', '.trash');
        $globalTrashRetentionDays = Arr::integer($config, 'trash.retention_days', 30);
        $globalDuplicatePolicyStr = Arr::string($config, 'upload.duplicate_policy', 'auto_rename');
        $globalDuplicatePolicy    = DuplicatePolicy::tryFrom($globalDuplicatePolicyStr) ?? DuplicatePolicy::AUTO_RENAME;
        $globalQuotas             = Arr::get($config, 'stats.quotas', []);

        $disks = [];
        foreach ($availableDisks as $diskName) {
            if (! Arr::has($filesystemsDisks, $diskName)) {
                continue;
            }

            $disks[$diskName] = $this->buildSingleDisk(
                $diskName,
                $rawConfigs,
                $filesystemsDisks,
                $globalTrashEnabled,
                $globalTrashFolder,
                $globalTrashRetentionDays,
                $globalDuplicatePolicy,
                $globalDuplicatePolicyStr,
                $globalQuotas,
            );
        }

        return $disks;
    }

    /**
     * @param  array<string, array<string, scalar|null>>  $filesystemsDisks
     */
    private function buildSingleDisk(
        string $diskName,
        mixed $rawConfigs,
        array $filesystemsDisks,
        bool $globalTrashEnabled,
        string $globalTrashFolder,
        int $globalRetentionDays,
        DuplicatePolicy $globalDuplicatePolicy,
        string $globalDuplicatePolicyStr,
        mixed $globalQuotas,
    ): Disk {
        $opts           = $this->getDiskRawOptions($rawConfigs, $diskName);
        $rawDriver      = Arr::get($filesystemsDisks, "{$diskName}.driver");
        $driver         = is_string($rawDriver) ? $rawDriver : '';
        $resolvedDriver = $this->resolveDriver($diskName, $driver, $filesystemsDisks);

        $root        = Arr::get($opts, 'root');
        $root        = is_string($root)                 && mb_trim($root) !== '' ? mb_trim($root) : null;
        $maxFileSize = Arr::has($opts, 'max_file_size') && Arr::integer($opts, 'max_file_size') > 0
            ? Arr::integer($opts, 'max_file_size')
            : null;

        $trashOptions    = $this->resolveTrashOptions($opts, $globalTrashEnabled, $globalTrashFolder, $globalRetentionDays);
        $quota           = $this->resolveQuota($opts, $globalQuotas, $diskName);
        $dupPolicyStr    = Arr::string($opts, 'duplicate_policy', $globalDuplicatePolicyStr);
        $duplicatePolicy = DuplicatePolicy::tryFrom($dupPolicyStr) ?? $globalDuplicatePolicy;
        $fnPolicyStr     = Arr::string($opts, 'filename_policy', FilenamePolicy::STRICT->value);
        $filenamePolicy  = FilenamePolicy::tryFrom($fnPolicyStr) ?? FilenamePolicy::STRICT;

        $readOnly = Arr::boolean($opts, 'read_only', false)
            || Arr::boolean($filesystemsDisks, "{$diskName}.read-only", false);

        return new Disk(
            driver: $resolvedDriver,
            name: $diskName,
            throw: Arr::boolean($filesystemsDisks, "{$diskName}.throw", false),
            report: Arr::boolean($filesystemsDisks, "{$diskName}.report", false),
            readOnly: $readOnly,
            root: $root,
            allowedExtensions: $this->resolveAllowedExtensions($opts),
            trash: $trashOptions['trash'],
            trashFolder: $trashOptions['trashFolder'],
            trashRetentionDays: $trashOptions['trashRetentionDays'],
            maxFileSize: $maxFileSize,
            quota: $quota,
            duplicatePolicy: $duplicatePolicy,
            filenamePolicy: $filenamePolicy,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function getDiskRawOptions(mixed $rawConfigs, string $diskName): array
    {
        if (! is_array($rawConfigs) || ! isset($rawConfigs[$diskName]) || ! is_array($rawConfigs[$diskName])) {
            return [];
        }

        $opts = [];
        foreach ($rawConfigs[$diskName] as $k => $v) {
            if (is_string($k)) {
                $opts[$k] = $v;
            }
        }

        return $opts;
    }

    /**
     * @param  array<string, mixed>  $opts
     * @return list<string>|null
     */
    private function resolveAllowedExtensions(array $opts): ?array
    {
        if (! Arr::has($opts, 'allowed_extensions') || ! is_array($opts['allowed_extensions'])) {
            return null;
        }

        /** @var list<string> $rawExts */
        $rawExts = array_values(array_filter(
            $opts['allowed_extensions'],
            static fn (mixed $ext): bool => is_string($ext) && $ext !== ''
        ));

        return $rawExts;
    }

    /**
     * @param  array<string, mixed>  $opts
     * @return array{trash: bool, trashFolder: string, trashRetentionDays: int}
     */
    private function resolveTrashOptions(
        array $opts,
        bool $globalEnabled,
        string $globalFolder,
        int $globalRetentionDays,
    ): array {
        $trashRaw = Arr::get($opts, 'trash');
        if (is_bool($trashRaw)) {
            return [
                'trash'              => $trashRaw,
                'trashFolder'        => $globalFolder,
                'trashRetentionDays' => $globalRetentionDays,
            ];
        }

        if (is_array($trashRaw)) {
            return [
                'trash'              => Arr::boolean($trashRaw, 'enabled', $globalEnabled),
                'trashFolder'        => Arr::string($trashRaw, 'folder', $globalFolder),
                'trashRetentionDays' => Arr::integer($trashRaw, 'retention_days', $globalRetentionDays),
            ];
        }

        return [
            'trash'              => $globalEnabled,
            'trashFolder'        => $globalFolder,
            'trashRetentionDays' => $globalRetentionDays,
        ];
    }

    /**
     * @param  array<string, mixed>  $opts
     */
    private function resolveQuota(array $opts, mixed $globalQuotas, string $diskName): ?int
    {
        if (Arr::has($opts, 'quota') && Arr::integer($opts, 'quota') > 0) {
            return Arr::integer($opts, 'quota');
        }

        $fallbackQuota = is_array($globalQuotas) ? Arr::get($globalQuotas, $diskName) : null;

        return is_numeric($fallbackQuota) && (int) $fallbackQuota > 0 ? (int) $fallbackQuota : null;
    }

    /**
     * @param  array<string, array<string, scalar|null>>  $fsConfig
     */
    private function resolveDriver(string $disk, ?string $driver, array $fsConfig): string
    {
        $currentDisk = $disk;

        if ($driver === null || $driver === '') {
            $rawDriver = Arr::get($fsConfig, "{$currentDisk}.driver");
            $driver    = is_string($rawDriver) ? $rawDriver : '';
        }

        $visited = [$currentDisk];

        while ($driver === 'scoped') {
            $parentDisk = Arr::get($fsConfig, "{$currentDisk}.disk");

            if (! is_string($parentDisk) || $parentDisk === '') {
                throw new \UnexpectedValueException("Scoped disk '{$currentDisk}' is missing a valid parent 'disk' configuration.");
            }

            if (in_array($parentDisk, $visited, true)) {
                throw new \UnexpectedValueException("Circular reference detected in scoped disk '{$currentDisk}'.");
            }

            $parentDriver = Arr::get($fsConfig, "{$parentDisk}.driver");

            if (! is_string($parentDriver) || $parentDriver === '') {
                throw new \UnexpectedValueException("Parent disk '{$parentDisk}' of scoped disk '{$currentDisk}' has no valid driver configured.");
            }

            $visited[]   = $parentDisk;
            $driver      = $parentDriver;
            $currentDisk = $parentDisk;
        }

        return $driver;
    }
}
