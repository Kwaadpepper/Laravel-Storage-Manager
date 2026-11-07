<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Query\Disk;

use Kwaadpepper\LaravelStorageManager\Acl\SmAbility;
use Kwaadpepper\LaravelStorageManager\Contract\Query;
use Kwaadpepper\LaravelStorageManager\Contract\QueryInput;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Service\DiskService;

/**
 * @implements Query<SelectDiskInput, Disk>
 */
final readonly class SelectDiskQuery implements Query
{
    public function __construct(
        private DiskService $diskService,
    ) {
    }

    public function ability(): string
    {
        return SmAbility::DISKS_SELECT;
    }

    /**
     * @param  SelectDiskInput  $input
     */
    public function __invoke(QueryInput $input): Disk
    {
        return $this->diskService->selectDisk($input->disk);
    }
}
