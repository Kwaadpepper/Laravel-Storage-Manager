<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Request\Upload\Traits;

use Kwaadpepper\LaravelStorageManager\Lib\Upload\UploadSessionService;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadId;
use Kwaadpepper\LaravelStorageManager\Rule\IsExistingUploadSession;

trait ValidatesUploadId
{
    public function getUploadId(): UploadId
    {
        return new UploadId($this->string('upload_id')->value());
    }

    /**
     * @return array<string,mixed>
     */
    protected function getUploadIdRules(): array
    {
        return [
            'upload_id' => [
                'required',
                'string',
                new IsExistingUploadSession(resolve(UploadSessionService::class)),
            ],
        ];
    }
}
