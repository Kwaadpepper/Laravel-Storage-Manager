<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Rule;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path;

class IsValidPath implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): PotentiallyTranslatedString  $fail
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
            return new Path($value) instanceof Path;
        } catch (\DomainException) {
            return false;
        }
    }
}
