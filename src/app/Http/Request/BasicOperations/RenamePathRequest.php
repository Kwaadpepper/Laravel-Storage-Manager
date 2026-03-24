<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Request\BasicOperations;

use Kwaadpepper\LaravelStorageManager\Http\Request\RequestWithPath;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathSanitizer;

class RenamePathRequest extends RequestWithPath
{
    private PathSanitizer $pathSanitizer;

    public function prepareForValidation()
    {
        parent::prepareForValidation();
        $this->pathSanitizer = resolve(PathSanitizer::class);

        $to = $this->string('to')->value();

        if (empty($to)) {
            return;
        }

        $this->merge([
            'to' => $this->pathSanitizer->sanitizeFileName($to),
        ]);
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'to' => ['required', 'string', 'min:1'],
        ]);
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'to' => trans('storage-manager::storage-manager.attribute.to'),
        ]);
    }
}
