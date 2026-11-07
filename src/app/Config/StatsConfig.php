<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Config;

final readonly class StatsConfig
{
    /**
     * @param  array<string, int>  $quotas
     */
    public function __construct(
        public bool $enabled,
        public int $cacheDuration,
        public array $quotas
    ) {
    }

    public function getDiskQuota(string $diskName): ?int
    {
        return $this->quotas[$diskName] ?? null;
    }
}
