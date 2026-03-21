<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Request\FileManager;

use Kwaadpepper\LaravelStorageManager\Http\Request\RequestWithDisk;
use Kwaadpepper\LaravelStorageManager\Rule\IsValidPath;

final class FmTreeRequest extends RequestWithDisk
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'path' => ['required', 'string', new IsValidPath()],
        ]);
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'path' => trans('storage-manager::storage-manager.attribute.path'),
        ]);
    }
}
