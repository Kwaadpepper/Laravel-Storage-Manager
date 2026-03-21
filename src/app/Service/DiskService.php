<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Service;

use Illuminate\Support\Arr;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Repository\ConfigRepository;

class DiskService
{
    public function __construct(
        private readonly ConfigRepository $configRepository
    ) {
    }

    /**
     * @return string[]
     */
    public function getDiskNamesList(): array
    {
        return array_values(array_map(
            fn (Disk $disk): string => $disk->name,
            $this->getConfiguredDisks()
        ));
    }

    public function getDisk(string $name): Disk
    {
        return Arr::first(
            $this->getConfiguredDisks(),
            fn (Disk $disk): bool => $disk->name === $name
        ) ?? throw new \InvalidArgumentException(
            "Disk with name '{$name}' not found."
        );
    }

    /**
     * @return array<string,Disk>
     */
    private function getConfiguredDisks(): array
    {
        return $this->configRepository->getDisksMap();
    }
}
