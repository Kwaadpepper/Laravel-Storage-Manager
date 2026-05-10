<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Request\BasicOperations;

use Kwaadpepper\LaravelStorageManager\Http\Request\RequestWithPath;

class DeletePathRequest extends RequestWithPath
{
    public function rules(): array
    {
        $parentRules = parent::rules();

        if (! isset($parentRules['path']) || ! is_array($parentRules['path'])) {
            throw new \LogicException('Expected parent rules to contain a "path" array.');
        }

        return array_merge($parentRules, [
            'path' => array_merge(
                $parentRules['path'],
                [
                    function (string $_, $value, callable $fail) {
                        if (preg_match('/^\/?$/', $value)) {
                            $fail(trans('storage-manager::storage-manager.validation.cannot_delete_root_path'));
                        }
                    },
                ]
            ),
        ]);
    }
}
