<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Request\Disk;

use Kwaadpepper\LaravelStorageManager\Http\Request\RequestWithDisk;

final class DiskStatsRequest extends RequestWithDisk
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'fresh' => ['sometimes', 'boolean'],
        ]);
    }
}
