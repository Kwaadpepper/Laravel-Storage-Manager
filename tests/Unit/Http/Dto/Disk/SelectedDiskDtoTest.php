<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Dto\Disk;

use Kwaadpepper\LaravelStorageManager\Http\Dto\Disk\SelectedDiskDto;

describe('SelectedDiskDto', function (): void {
    it('serializes disk properties correctly', function (): void {
        // GIVEN
        $dto = new SelectedDiskDto(
            driver: 's3',
            name: 's3_public',
            throw: true,
            report: false,
            readOnly: true,
            trash: false,
            allowedExtensions: ['jpg', 'png'],
            maxFileSize: 1000000,
            filenamePolicy: 'permissive',
            duplicatePolicy: 'overwrite',
        );

        // WHEN
        $serialized = $dto->jsonSerialize();

        // THEN
        expect($serialized)->toBe([
            'disk' => [
                'driver'            => 's3',
                'name'              => 's3_public',
                'throw'             => true,
                'report'            => false,
                'readOnly'          => true,
                'trash'             => false,
                'allowedExtensions' => ['jpg', 'png'],
                'maxFileSize'       => 1000000,
                'filenamePolicy'    => 'permissive',
                'duplicatePolicy'   => 'overwrite',
            ],
        ]);
    });
})->covers(SelectedDiskDto::class);
