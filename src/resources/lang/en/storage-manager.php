<?php

declare(strict_types=1);

return [
    'error' => [
        'unknown' => 'An unknown error occurred.',
    ],
    'validation' => [
        'invalid_path'            => 'The :attribute field must be a valid path.',
        'invalid_directory_name'  => 'The :attribute field must be a valid directory name.',
        'invalid_file_name'       => 'The :attribute field must be a valid file name.',
        'cannot_delete_root_path' => 'The root path cannot be deleted.',
    ],
    'attribute' => [
        'disk' => 'disk',
        'path' => 'path',
        'name' => 'name',
        'to'   => 'new name',
    ],
];
