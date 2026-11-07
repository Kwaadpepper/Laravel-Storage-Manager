<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Rule;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Lang;
use Kwaadpepper\LaravelStorageManager\Exception\IllegalDomainStateException;
use Kwaadpepper\LaravelStorageManager\Lib\Upload\UploadSessionService;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadId;

class IsExistingUploadSession implements ValidationRule
{
    public function __construct(
        private readonly UploadSessionService $uploadSessionService
    ) {
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string,?string=):\Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (! is_string($value)) {
            $fail((string) Lang::get('storage-manager::storage-manager.validation.invalid_upload_id'));

            return;
        }

        try {
            $uploadId = new UploadId($value);
        } catch (\InvalidArgumentException | \DomainException | IllegalDomainStateException) {
            $fail((string) Lang::get('storage-manager::storage-manager.validation.invalid_upload_id'));

            return;
        }

        if (! $this->uploadSessionService->sessionExists($uploadId)) {
            $fail((string) Lang::get('storage-manager::storage-manager.validation.upload_session_not_found'));
        }
    }
}
