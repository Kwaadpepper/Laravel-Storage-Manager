<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Action\Upload;

use Kwaadpepper\LaravelStorageManager\Acl\SmAbility;
use Kwaadpepper\LaravelStorageManager\Contract\Action;
use Kwaadpepper\LaravelStorageManager\Contract\ActionInput;
use Kwaadpepper\LaravelStorageManager\Lib\Upload\UploadSessionService;

/**
 * @implements Action<InitUploadInput, void>
 */
final readonly class InitUploadAction implements Action
{
    public function __construct(
        private UploadSessionService $sessionService,
    ) {
    }

    public function ability(): string
    {
        return SmAbility::UPLOAD_INIT;
    }

    /**
     * @param  InitUploadInput  $input
     */
    public function __invoke(ActionInput $input): void
    {
        $this->sessionService->createSession($input->uploadId, $input->metadata);
    }
}
