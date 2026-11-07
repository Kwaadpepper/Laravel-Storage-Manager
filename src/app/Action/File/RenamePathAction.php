<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Action\File;

use Kwaadpepper\LaravelStorageManager\Acl\SmAbility;
use Kwaadpepper\LaravelStorageManager\Contract\Action;
use Kwaadpepper\LaravelStorageManager\Contract\ActionInput;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\FileTransferService;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Psr\Log\LoggerInterface;

/**
 * @implements Action<RenamePathInput, Path>
 */
final readonly class RenamePathAction implements Action
{
    public function __construct(
        private FileTransferService $fileTransferService,
        private LoggerInterface $logger,
    ) {
    }

    public function ability(): string
    {
        return SmAbility::RENAME;
    }

    /**
     * @param  RenamePathInput  $input
     *
     * @throws FileOperationException
     */
    public function __invoke(ActionInput $input): Path
    {
        $this->logger->debug('Delegating rename to FileTransferService', [
            'disk'    => $input->disk->name,
            'source'  => $input->path->value,
            'newName' => $input->newName,
        ]);

        return $this->fileTransferService->rename($input->disk, $input->path, $input->newName);
    }
}
