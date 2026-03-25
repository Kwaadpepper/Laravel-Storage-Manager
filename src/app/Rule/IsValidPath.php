<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Rule;

use Illuminate\Contracts\Validation\ValidationRule;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;

class IsValidPath implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string,?string=):\Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (! $this->pathIsValid($value)) {
            $fail(trans('storage-manager::storage-manager.validation.invalid_path'));
        }
    }

    private function pathIsValid(mixed $value): bool
    {
        try {
            assert(
                ! empty((new Path($value))->value),
                'Path must be a non-empty string'
            );

            return true;
        } catch (\DomainException) {
            return false;
        }
    }
}
