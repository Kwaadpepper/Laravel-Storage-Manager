<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Action\File;

use Kwaadpepper\LaravelStorageManager\Acl\SmAbility;
use Kwaadpepper\LaravelStorageManager\Contract\Action;
use Kwaadpepper\LaravelStorageManager\Contract\ActionInput;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\FileTransferService;
use Psr\Log\LoggerInterface;

/**
 * @implements Action<BulkRenameInput, array<int, array{from: string, to: string}>>
 */
final readonly class BulkRenameAction implements Action
{
    public function __construct(
        private FileTransferService $fileTransferService,
        private LoggerInterface $logger,
    ) {
    }

    public function ability(): string
    {
        return SmAbility::BULK_RENAME;
    }

    /**
     * @param  BulkRenameInput  $input
     * @return array<int, array{from: string, to: string}>
     *
     * @throws FileOperationException
     */
    public function __invoke(ActionInput $input): array
    {
        $this->logger->debug('Delegating bulkRename to FileTransferService', [
            'disk'  => $input->disk->name,
            'count' => count($input->renames),
        ]);

        return $this->fileTransferService->bulkRename($input->disk, $input->renames);
    }
}
