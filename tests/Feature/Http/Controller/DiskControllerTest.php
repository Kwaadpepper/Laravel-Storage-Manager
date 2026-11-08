<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Http\Controller\DiskController;

describe('DiskController', function (): void {
    beforeEach(function (): void {
        Storage::fake('local');
    });

    it('lists configured disks', function (): void {
        // GIVEN
        $url = route('storage-manager.api.disks.list');

        // WHEN
        $response = $this->getJson($url);

        // THEN
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'disks',
                ],
            ]);
        expect($response->json('data.disks'))->toBeArray();
        expect($response->json('data.disks'))->toContain('local');
    });

    it('selects an existing configured disk', function (): void {
        // GIVEN
        $url = route('storage-manager.api.disks.select');

        // WHEN
        $response = $this->postJson($url, [
            'disk' => 'local',
        ]);

        // THEN
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'disk' => [
                        'driver',
                        'name',
                        'throw',
                        'report',
                        'readOnly',
                        'trash',
                        'allowedExtensions',
                        'maxFileSize',
                        'filenamePolicy',
                        'duplicatePolicy',
                    ],
                ],
            ]);
        expect($response->json('data.disk.name'))->toBe('local');
        expect($response->json('data.disk.driver'))->toBe('local');
    });

    it('rejects selecting non-existent or invalid disk', function (): void {
        // GIVEN
        $url = route('storage-manager.api.disks.select');

        // WHEN
        $response = $this->postJson($url, [
            'disk' => 'non_existent_disk_999',
        ]);

        // THEN
        $response->assertStatus(422);
    });
})->covers(DiskController::class);
