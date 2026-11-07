<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Request\BasicOperations;

use Kwaadpepper\LaravelStorageManager\Http\Request\RequestWithPath;
use Kwaadpepper\LaravelStorageManager\Rule\IsNotRootPath;

class DeletePathRequest extends RequestWithPath
{
    /**
     * @return array<string, mixed>
     */
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
                    new IsNotRootPath(),
                ]
            ),
            'force' => ['sometimes', 'boolean'],
        ]);
    }
}
