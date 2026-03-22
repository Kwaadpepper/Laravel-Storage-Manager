<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Request\BasicOperations;

use Kwaadpepper\LaravelStorageManager\Http\Request\RequestWithPath;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathSanitizer;

class CreateDirectoryRequest extends RequestWithPath
{
    private PathSanitizer $pathSanitizer;

    public function prepareForValidation()
    {
        parent::prepareForValidation();
        $this->pathSanitizer = resolve(PathSanitizer::class);

        $directoryName = $this->input('name', '');

        if (empty($directoryName) || ! is_string($directoryName)) {
            return;
        }

        $this->merge([
            'name' => $this->pathSanitizer->sanitizeDirectoryName($directoryName),
        ]);
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'name' => [
                'required',
                'string',
                function (string $_, $value, callable $fail) {
                    if (preg_match('/[\/\\\\]/', $value)) {
                        $fail(trans('storage-manager::storage-manager.validation.invalid_directory_name'));
                    }
                },
            ],
        ]);
    }
}
