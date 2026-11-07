<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Acl;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;

final readonly class AclManager
{
    public function __construct(
        private StorageManagerConfig $config,
    ) {
    }

    public function allows(string $ability, ?Authenticatable $user, ?Disk $disk = null, ?Path $path = null): bool
    {
        if ($this->config->auth->enabled && $user === null) {
            return false;
        }

        if (SmAbility::isMutation($ability) && $disk !== null && $disk->readOnly) {
            return false;
        }

        return true;
    }

    public function registerDefaultGates(Gate $gate): void
    {
        foreach (SmAbility::ALL as $ability) {
            if (! $gate->has($ability)) {
                $gate->define(
                    $ability,
                    function (?Authenticatable $user = null, ?Disk $disk = null, ?Path $path = null) use ($ability): bool {
                        return $this->allows($ability, $user, $disk, $path);
                    }
                );
            }
        }
    }
}
