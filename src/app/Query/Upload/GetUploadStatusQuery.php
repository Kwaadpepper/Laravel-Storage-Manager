<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Query\Upload;

use Kwaadpepper\LaravelStorageManager\Acl\SmAbility;
use Kwaadpepper\LaravelStorageManager\Contract\Query;
use Kwaadpepper\LaravelStorageManager\Contract\QueryInput;
use Kwaadpepper\LaravelStorageManager\Lib\Upload\UploadSessionService;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadSessionStatus;

/**
 * @implements Query<GetUploadStatusInput, UploadSessionStatus>
 */
final readonly class GetUploadStatusQuery implements Query
{
    public function __construct(
        private UploadSessionService $sessionService,
    ) {
    }

    public function ability(): string
    {
        return SmAbility::UPLOAD_STATUS;
    }

    /**
     * @param  GetUploadStatusInput  $input
     */
    public function __invoke(QueryInput $input): UploadSessionStatus
    {
        return $this->sessionService->readStatus($input->uploadId) ?? UploadSessionStatus::waiting();
    }
}
