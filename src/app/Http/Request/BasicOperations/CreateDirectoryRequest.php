<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Request\BasicOperations;

use Kwaadpepper\LaravelStorageManager\Enum\FilenamePolicy;
use Kwaadpepper\LaravelStorageManager\Http\Request\RequestWithPath;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathSanitizer;
use Kwaadpepper\LaravelStorageManager\Rule\IsValidDirectoryName;

class CreateDirectoryRequest extends RequestWithPath
{
    private PathSanitizer $pathSanitizer;

    private FilenamePolicy $filenamePolicy;

    public function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->pathSanitizer = resolve(PathSanitizer::class);

        $disk                 = $this->getDisk();
        $this->filenamePolicy = $disk->filenamePolicy;

        $directoryName = $this->input('name', '');

        if (empty($directoryName) || ! is_string($directoryName)) {
            return;
        }

        $this->merge([
            'name' => $this->pathSanitizer->sanitizeDirectoryName($directoryName, true, $this->filenamePolicy),
        ]);
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'name' => [
                'required',
                'string',
                new IsValidDirectoryName($this->filenamePolicy),
            ],
        ]);
    }
}
