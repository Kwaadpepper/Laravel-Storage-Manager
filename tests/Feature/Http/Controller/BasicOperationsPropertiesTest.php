<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;

describe('BasicOperationsProperties', function (): void {
    it('returns full metadata for an existing file', function (): void {
        Storage::fake('local');
        $content = 'Sample content for metadata test';
        Storage::disk('local')->put('documents/file.txt', $content);

        $response = $this->getJson(route('storage-manager.api.fm.properties', [
            'disk' => 'local',
            'path' => '/documents/file.txt',
        ]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'path',
                    'basename',
                    'dirname',
                    'timestamp',
                    'visibility',
                    'isFile',
                    'extension',
                    'size',
                    'mimeType',
                    'publicUrl',
                    'checksum',
                    'dimensions',
                    'exif',
                    'rawExif',
                ],
            ]);

        expect($response->json('data.path'))->toBe('/documents/file.txt');
        expect($response->json('data.basename'))->toBe('file.txt');
        expect($response->json('data.extension'))->toBe('txt');
        expect($response->json('data.size'))->toBe(strlen($content));
        expect($response->json('data.checksum'))->toBe(md5($content));
        expect($response->json('data.isFile'))->toBe(true);
        expect($response->json('data.isDirectory'))->toBeNull();
    });

    it('returns directory properties for an existing directory', function (): void {
        Storage::fake('local');
        Storage::disk('local')->makeDirectory('my-folder');

        $response = $this->getJson(route('storage-manager.api.fm.properties', [
            'disk' => 'local',
            'path' => '/my-folder',
        ]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'path',
                    'basename',
                    'dirname',
                    'timestamp',
                    'visibility',
                    'isDirectory',
                ],
            ]);

        expect($response->json('data.path'))->toBe('/my-folder');
        expect($response->json('data.basename'))->toBe('my-folder');
        expect($response->json('data.isDirectory'))->toBe(true);
        expect($response->json('data.isFile'))->toBeNull();
    });

    it('returns 404 for a non-existent path', function (): void {
        Storage::fake('local');

        $response = $this->getJson(route('storage-manager.api.fm.properties', [
            'disk' => 'local',
            'path' => '/does-not-exist.txt',
        ]));

        $response->assertStatus(404);
        expect($response->json('errors.error'))->toBe('The specified path does not exist.');
    });
});
