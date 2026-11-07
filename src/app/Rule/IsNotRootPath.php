<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Rule;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Lang;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathNormalizer;

class IsNotRootPath implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string,?string=):\Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        $normalizer = resolve(PathNormalizer::class);

        if ($normalizer->isRootPath($value)) {
            $fail((string) Lang::get('storage-manager::storage-manager.validation.cannot_delete_root_path'));
        }
    }
}
