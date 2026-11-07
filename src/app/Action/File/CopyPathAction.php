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
 * @implements Action<CopyPathInput, Path>
 */
final readonly class CopyPathAction implements Action
{
    public function __construct(
        private FileTransferService $fileTransferService,
        private LoggerInterface $logger,
    ) {
    }

    public function ability(): string
    {
        return SmAbility::COPY;
    }

    /**
     * @param  CopyPathInput  $input
     *
     * @throws FileOperationException
     */
    public function __invoke(ActionInput $input): Path
    {
        $this->logger->debug('Delegating copy to FileTransferService', [
            'disk'           => $input->disk->name,
            'source'         => $input->path->value,
            'destinationDir' => $input->destinationDir->value,
        ]);

        return $this->fileTransferService->copy($input->disk, $input->path, $input->destinationDir);
    }
}
