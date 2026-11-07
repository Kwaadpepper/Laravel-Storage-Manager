<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Action\Archive;

use Kwaadpepper\LaravelStorageManager\Acl\SmAbility;
use Kwaadpepper\LaravelStorageManager\Contract\Action;
use Kwaadpepper\LaravelStorageManager\Contract\ActionInput;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Service\ArchiveService;

/**
 * @implements Action<ZipInput, Path>
 */
final readonly class ZipAction implements Action
{
    public function __construct(
        private ArchiveService $archiveService,
    ) {
    }

    public function ability(): string
    {
        return SmAbility::ZIP;
    }

    /**
     * @param  ZipInput  $input
     *
     * @throws FileOperationException
     */
    public function __invoke(ActionInput $input): Path
    {
        return $this->archiveService->zip(
            $input->disk,
            $input->paths,
            $input->destination
        );
    }
}
