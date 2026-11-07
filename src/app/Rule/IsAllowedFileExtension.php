<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Rule;

use Illuminate\Contracts\Validation\ValidationRule;
use Kwaadpepper\LaravelStorageManager\Config\UploadConfig;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;

class IsAllowedFileExtension implements ValidationRule
{
    public function __construct(
        private readonly UploadConfig $uploadConfig
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
            return;
        }

        $extension = strtolower(pathinfo($value, PATHINFO_EXTENSION));
        if (! $this->uploadConfig->isExtensionAllowed($extension)) {
            $fail(FileOperationError::FORBIDDEN_FILE_EXTENSION->message());
        }
    }
}
