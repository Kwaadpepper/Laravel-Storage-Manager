<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Request\BasicOperations;

use Kwaadpepper\LaravelStorageManager\Http\Request\RequestWithPath;

class DeletePathRequest extends RequestWithPath
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'path' => array_merge(
                parent::rules()['path'],
                [
                    function (string $_, $value, callable $fail) {
                        if (preg_match('/^\/?$/', $value)) {
                            $fail(trans('storage-manager::storage-manager.validation.path.cannot_delete_root'));
                        }
                    },
                ]
            ),
        ]);
    }
}
