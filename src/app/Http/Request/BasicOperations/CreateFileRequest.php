<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Request\BasicOperations;

use Kwaadpepper\LaravelStorageManager\Enum\FilenamePolicy;
use Kwaadpepper\LaravelStorageManager\Http\Request\RequestWithPath;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathSanitizer;
use Kwaadpepper\LaravelStorageManager\Rule\IsValidFileName;

class CreateFileRequest extends RequestWithPath
{
    private PathSanitizer $pathSanitizer;

    private FilenamePolicy $filenamePolicy;

    public function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->pathSanitizer = resolve(PathSanitizer::class);

        $disk                 = $this->getDisk();
        $this->filenamePolicy = $disk->filenamePolicy;

        $fileName = $this->input('name', '');

        if (empty($fileName) || ! is_string($fileName)) {
            return;
        }

        $this->merge([
            'name'    => $this->pathSanitizer->sanitizeFileName($fileName, true, $this->filenamePolicy),
            'content' => $this->string('content', '')->value(),
        ]);
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'name' => [
                'required',
                'string',
                new IsValidFileName($this->filenamePolicy),
            ],
            'content' => [
                'present',
                'string',
            ],
        ]);
    }
}
