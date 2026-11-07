<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Query\Disk;

use Kwaadpepper\LaravelStorageManager\Acl\SmAbility;
use Kwaadpepper\LaravelStorageManager\Contract\Query;
use Kwaadpepper\LaravelStorageManager\Contract\QueryInput;
use Kwaadpepper\LaravelStorageManager\Service\DiskService;

/**
 * @implements Query<GetConfiguredDisksInput, list<string>>
 */
final readonly class GetConfiguredDisksQuery implements Query
{
    public function __construct(
        private DiskService $diskService,
    ) {
    }

    public function ability(): string
    {
        return SmAbility::DISKS_LIST;
    }

    /**
     * @param  GetConfiguredDisksInput  $input
     * @return list<string>
     */
    public function __invoke(QueryInput $input): array
    {
        return $this->diskService->getDiskNamesList();
    }
}
