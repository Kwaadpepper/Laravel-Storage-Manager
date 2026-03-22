<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Request;

use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Rule\IsValidPath;

class RequestWithPath extends RequestWithDisk
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

    public function getPath(): Path
    {
        return new Path($this->string('path')->value());
    }
}
