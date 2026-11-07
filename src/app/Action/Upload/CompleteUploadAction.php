<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Action\Upload;

use Kwaadpepper\LaravelStorageManager\Acl\SmAbility;
use Kwaadpepper\LaravelStorageManager\Contract\Action;
use Kwaadpepper\LaravelStorageManager\Contract\ActionInput;
use Kwaadpepper\LaravelStorageManager\Exception\FileOperationException;
use Kwaadpepper\LaravelStorageManager\Lib\Upload\UploadSessionService;

/**
 * @implements Action<CompleteUploadInput, string>
 */
final readonly class CompleteUploadAction implements Action
{
    public function __construct(
        private UploadSessionService $sessionService,
    ) {
    }

    public function ability(): string
    {
        return SmAbility::UPLOAD_COMPLETE;
    }

    /**
     * @param  CompleteUploadInput  $input
     *
     * @throws FileOperationException
     */
    public function __invoke(ActionInput $input): string
    {
        return $this->sessionService->completeUpload(
            uploadId: $input->uploadId,
            disk: $input->disk,
            destinationDir: $input->destinationDir,
            fileName: $input->fileName,
            requestedPolicy: $input->requestedPolicy,
            onProgress: $input->onProgress
        );
    }
}
