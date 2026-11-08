<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Factory;

use Illuminate\Support\Arr;
use Kwaadpepper\LaravelStorageManager\Config\UploadConfig;
use Kwaadpepper\LaravelStorageManager\Enum\DuplicatePolicy;

class UploadConfigFactory
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function build(array $config): UploadConfig
    {
        $phpMax      = $this->calculatePhpMaxUploadSize();
        [$min, $max] = $this->resolveChunkSizes($config, $phpMax);

        $tempDisk = Arr::get($config, 'upload.temp_disk');
        $tempDisk = is_string($tempDisk) && $tempDisk !== '' ? $tempDisk : null;

        $maxFileSize = Arr::has($config, 'upload.max_file_size') && Arr::integer($config, 'upload.max_file_size') > 0
            ? Arr::integer($config, 'upload.max_file_size')
            : null;

        return new UploadConfig(
            $min,
            $max,
            $tempDisk,
            $this->resolveTempPath($config),
            $this->resolveDuplicatePolicy($config),
            Arr::boolean($config, 'upload.sanitize_filenames', true),
            $this->resolveAllowedExtensions($config),
            $maxFileSize
        );
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{0: int, 1: int}
     */
    private function resolveChunkSizes(array $config, int $phpMax): array
    {
        $rawMax = Arr::get($config, 'upload.chunk_max_size', 20 * 1024 * 1024);
        $max    = is_numeric($rawMax) ? (int) $rawMax : 20 * 1024 * 1024;
        if ($phpMax > 0 && $max > $phpMax) {
            $max = $phpMax;
        }

        $rawMin = Arr::get($config, 'upload.chunk_min_size', 2 * 1024 * 1024);
        $min    = is_numeric($rawMin) ? (int) $rawMin : 2 * 1024 * 1024;
        if ($phpMax > 0 && $min > $phpMax) {
            $min = $phpMax;
        }
        if ($min > $max) {
            $min = $max;
        }

        return [$min, $max];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function resolveTempPath(array $config): string
    {
        $tempPath = Arr::get($config, 'upload.temp_path', 'lsm_uploads');
        if (! is_string($tempPath) || $tempPath === '') {
            $tempPath = 'lsm_uploads';
        }
        if (function_exists('posix_getuid')) {
            $tempPath .= '_' . posix_getuid();
        }

        return $tempPath;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function resolveDuplicatePolicy(array $config): DuplicatePolicy
    {
        $policyVal = Arr::get($config, 'upload.duplicate_policy', 'auto_rename');
        $policyVal = is_string($policyVal) ? $policyVal : 'auto_rename';

        return DuplicatePolicy::tryFrom($policyVal) ?? DuplicatePolicy::AUTO_RENAME;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return list<string>
     */
    private function resolveAllowedExtensions(array $config): array
    {
        $rawAllowed = Arr::get($config, 'upload.allowed_extensions', []);
        if (is_string($rawAllowed)) {
            $rawAllowed = explode(',', $rawAllowed);
        }
        if (! is_array($rawAllowed)) {
            $rawAllowed = [];
        }

        $allowed = [];
        foreach ($rawAllowed as $ext) {
            if (! is_string($ext)) {
                continue;
            }
            $normalized = strtolower(ltrim(mb_trim($ext), '.'));
            if ($normalized === '' || in_array($normalized, UploadConfig::UNSUPPORTED_EXTENSIONS, true)) {
                continue;
            }
            $allowed[] = $normalized;
        }

        return array_values(array_unique($allowed));
    }

    private function calculatePhpMaxUploadSize(): int
    {
        $postMax   = $this->parseIniBytes(ini_get('post_max_size'));
        $uploadMax = $this->parseIniBytes(ini_get('upload_max_filesize'));

        if ($postMax === 0 && $uploadMax === 0) {
            return 0; // Unlimited
        }

        if ($postMax === 0) {
            return $uploadMax;
        }

        if ($uploadMax === 0) {
            return $postMax;
        }

        return min($postMax, $uploadMax);
    }

    private function parseIniBytes(string | false $value): int
    {
        if (! $value) {
            return 0;
        }

        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        $last = strtolower($value[strlen($value) - 1]);
        $val  = (int) $value;

        switch ($last) {
            case 'g':
                $val *= 1024;
                // no break
            case 'm':
                $val *= 1024;
                // no break
            case 'k':
                $val *= 1024;
        }

        return $val;
    }
}
