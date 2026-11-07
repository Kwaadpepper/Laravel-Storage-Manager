<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Query\File;

use Kwaadpepper\LaravelStorageManager\Acl\SmAbility;
use Kwaadpepper\LaravelStorageManager\Contract\Query;
use Kwaadpepper\LaravelStorageManager\Contract\QueryInput;
use Kwaadpepper\LaravelStorageManager\Lib\StorageGateway;

/**
 * @implements Query<CheckPathExistsInput, bool>
 */
final readonly class CheckPathExistsQuery implements Query
{
    public function __construct(
        private StorageGateway $storageGateway,
    ) {
    }

    public function ability(): string
    {
        return SmAbility::VIEW;
    }

    /**
     * @param  CheckPathExistsInput  $input
     */
    public function __invoke(QueryInput $input): bool
    {
        return $this->storageGateway->exists($input->disk, $input->path);
    }
}
