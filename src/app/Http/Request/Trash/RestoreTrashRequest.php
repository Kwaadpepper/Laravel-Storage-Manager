<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Request\Trash;

use Kwaadpepper\LaravelStorageManager\Http\Request\RequestWithDisk;

class RestoreTrashRequest extends RequestWithDisk
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'string'],
        ]);
    }

    /**
     * @return list<string>
     */
    public function getIds(): array
    {
        /** @var list<string> $ids */
        $ids = array_values($this->array('ids'));

        return $ids;
    }
}
