<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Request\BasicOperations;

use Illuminate\Support\Facades\Lang;
use Kwaadpepper\LaravelStorageManager\Enum\FilenamePolicy;
use Kwaadpepper\LaravelStorageManager\Http\Request\RequestWithPath;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathSanitizer;
use Kwaadpepper\LaravelStorageManager\Rule\IsValidFileName;

class RenamePathRequest extends RequestWithPath
{
    private PathSanitizer $pathSanitizer;

    private FilenamePolicy $filenamePolicy;

    public function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->pathSanitizer  = resolve(PathSanitizer::class);
        $this->filenamePolicy = $this->getDisk()->filenamePolicy;

        $to = $this->string('to')->value();

        if (empty($to)) {
            return;
        }

        $this->merge([
            'to' => $this->pathSanitizer->sanitizeFileName($to, true, $this->filenamePolicy),
        ]);
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'to' => ['required', 'string', 'min:1', new IsValidFileName($this->filenamePolicy)],
        ]);
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'to' => (string) Lang::get('storage-manager::storage-manager.attribute.to'),
        ]);
    }
}
