<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Factory;

use Illuminate\Support\Arr;
use Kwaadpepper\LaravelStorageManager\Config\TrashConfig;

class TrashConfigFactory
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function build(array $config): TrashConfig
    {
        $enabled = Arr::boolean($config, 'trash.enabled', true);

        $folder = Arr::get($config, 'trash.folder', '.trash');
        $folder = is_string($folder) && $folder !== '' ? $folder : '.trash';

        $retentionDays = Arr::get($config, 'trash.retention_days', 30);
        $retentionDays = is_int($retentionDays) && $retentionDays >= 0 ? $retentionDays : 30;

        return new TrashConfig($enabled, $folder, $retentionDays);
    }
}
