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
        $enabled       = Arr::boolean($config, 'trash.enabled', true);
        $folder        = Arr::string($config, 'trash.folder', '.trash');
        $folder        = $folder !== '' ? $folder : '.trash';
        $retentionDays = Arr::integer($config, 'trash.retention_days', 30);

        return new TrashConfig($enabled, $folder, $retentionDays >= 0 ? $retentionDays : 30);
    }
}
