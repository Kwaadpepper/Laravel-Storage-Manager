<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Rule;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Lang;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathSanitizer;

class IsValidDirectoryName implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string,?string=):\Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        $sanitizer = resolve(PathSanitizer::class);

        if (! $sanitizer->isValidDirectoryName($value)) {
            $fail((string) Lang::get('storage-manager::storage-manager.validation.invalid_directory_name'));
        }
    }
}
