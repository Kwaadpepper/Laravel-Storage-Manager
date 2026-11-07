<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Action\Upload;

use Kwaadpepper\LaravelStorageManager\Acl\SmAbility;
use Kwaadpepper\LaravelStorageManager\Contract\Action;
use Kwaadpepper\LaravelStorageManager\Contract\ActionInput;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Lib\Upload\UploadSessionService;

/**
 * @implements Action<UploadChunkInput, void>
 */
final readonly class UploadChunkAction implements Action
{
    public function __construct(
        private UploadSessionService $sessionService,
    ) {
    }

    public function ability(): string
    {
        return SmAbility::UPLOAD_CHUNK;
    }

    /**
     * @param  UploadChunkInput  $input
     *
     * @throws FileOperationException
     */
    public function __invoke(ActionInput $input): void
    {
        $this->sessionService->storeChunk(
            uploadId: $input->uploadId,
            chunkIndex: $input->chunkIndex,
            file: $input->file
        );
    }
}
