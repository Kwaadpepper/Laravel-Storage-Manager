<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Request\Upload\Traits;

use Kwaadpepper\LaravelStorageManager\Lib\Upload\UploadSessionService;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadId;

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
            'upload_id' => ['required', 'string', function (string $_, mixed $value, \Closure $fail) {
                try {
                    if (! is_string($value)) {
                        $fail('The upload_id must be a string.');

                        return;
                    }

                    $uploadId = new UploadId($value);

                    $uploadSessionService = resolve(UploadSessionService::class);
                    if (! $uploadSessionService->sessionExists($uploadId)) {
                        $fail('Upload session not found.');
                    }
                } catch (\InvalidArgumentException) {
                    $fail('The upload_id is invalid.');
                }
            }],
        ];
    }
}
