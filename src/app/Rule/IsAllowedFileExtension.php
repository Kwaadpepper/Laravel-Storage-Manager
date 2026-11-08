<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Rule;

use Illuminate\Contracts\Validation\ValidationRule;
use Kwaadpepper\LaravelStorageManager\Config\UploadConfig;
use Kwaadpepper\LaravelStorageManager\Enum\FileOperationError;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;

class IsAllowedFileExtension implements ValidationRule
{
    public function __construct(
        private readonly UploadConfig $uploadConfig,
        private readonly ?Disk $disk = null,
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

        $extension = strtolower(ltrim(pathinfo($value, PATHINFO_EXTENSION), '.'));
        if ($this->disk !== null) {
            if (! $this->disk->isExtensionAllowed($extension, $this->uploadConfig)) {
                $fail(FileOperationError::FORBIDDEN_FILE_EXTENSION->message());
            }

            return;
        }

        if (! $this->uploadConfig->isExtensionAllowed($extension)) {
            $fail(FileOperationError::FORBIDDEN_FILE_EXTENSION->message());
        }
    }
}
