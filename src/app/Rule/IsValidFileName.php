<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Rule;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Lang;
use Kwaadpepper\LaravelStorageManager\Enum\FilenamePolicy;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathSanitizer;

class IsValidFileName implements ValidationRule
{
    public function __construct(
        private ?FilenamePolicy $policy = null
    ) {
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string,?string=):\Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        $sanitizer = resolve(PathSanitizer::class);
        $policy    = $this->policy ?? FilenamePolicy::STRICT;

        if (! $sanitizer->isValidFileName($value, $policy)) {
            $fail((string) Lang::get('storage-manager::storage-manager.validation.invalid_file_name'));
        }
    }
}
