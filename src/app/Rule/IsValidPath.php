<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Rule;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Lang;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathNormalizer;

class IsValidPath implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string,?string=):\Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        $normalizer = resolve(PathNormalizer::class);

        if (! $normalizer->isValidPath($value)) {
            $fail((string) Lang::get('storage-manager::storage-manager.validation.invalid_path'));
        }
    }
}
