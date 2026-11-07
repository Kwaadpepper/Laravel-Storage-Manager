<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Query\Disk;

use Kwaadpepper\LaravelStorageManager\Acl\SmAbility;
use Kwaadpepper\LaravelStorageManager\Contract\Query;
use Kwaadpepper\LaravelStorageManager\Contract\QueryInput;
use Kwaadpepper\LaravelStorageManager\Http\Dto\Disk\DiskStatsDto;
use Kwaadpepper\LaravelStorageManager\Service\DiskService;

/**
 * @implements Query<GetDiskStatsInput, DiskStatsDto>
 */
final readonly class GetDiskStatsQuery implements Query
{
    public function __construct(
        private DiskService $diskService,
    ) {
    }

    public function ability(): string
    {
        return SmAbility::DISKS_STATS;
    }

    /**
     * @param  GetDiskStatsInput  $input
     */
    public function __invoke(QueryInput $input): DiskStatsDto
    {
        if ($input->fresh) {
            $this->diskService->clearDiskStatsCache($input->disk);
        }

        return $this->diskService->getDiskStats($input->disk);
    }
}
