<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controller;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;

describe('ThumbnailController', function (): void {
    it('generates a webp thumbnail for a png image', function (): void {
        // GIVEN
        Storage::fake('local');

        $img = imagecreatetruecolor(20, 20);
        ob_start();
        imagepng($img);
        $pngData = (string) ob_get_clean();

        Storage::disk('local')->put('images/test.png', $pngData);

        // WHEN
        $response = $this->get(route('storage-manager.thumb', [
            'disk' => 'local',
            'path' => 'images/test.png',
        ]));

        // THEN
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/webp');
        expect($response->headers->get('Cache-Control'))->toContain('public');
        expect($response->headers->get('Cache-Control'))->toContain('max-age=604800');
        expect($response->headers->get('ETag'))->not->toBeNull();

        ob_start();
        $response->sendContent();
        $output = (string) ob_get_clean();

        expect(strlen($output))->toBeGreaterThan(0);
    });

    it('returns 304 Not Modified when If-None-Match header matches ETag', function (): void {
        // GIVEN
        Storage::fake('local');

        $img = imagecreatetruecolor(20, 20);
        ob_start();
        imagepng($img);
        $pngData = (string) ob_get_clean();

        Storage::disk('local')->put('images/test2.png', $pngData);

        $firstResponse = $this->get(route('storage-manager.thumb', [
            'disk' => 'local',
            'path' => 'images/test2.png',
        ]));

        $firstResponse->assertStatus(200);
        $etag = $firstResponse->headers->get('ETag');
        expect($etag)->not->toBeNull();

        // WHEN
        $secondResponse = $this->get(
            route('storage-manager.thumb', [
                'disk' => 'local',
                'path' => 'images/test2.png',
            ]),
            ['If-None-Match' => (string) $etag]
        );

        // THEN
        $secondResponse->assertStatus(304);
        $secondResponse->assertHeader('ETag', (string) $etag);
    });

    it('serves svg thumbnail directly with image/svg+xml content-type', function (): void {
        // GIVEN
        Storage::fake('local');

        $svgData = '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><circle cx="5" cy="5" r="5"/></svg>';
        Storage::disk('local')->put('icons/test.svg', $svgData);

        // WHEN
        $response = $this->get(route('storage-manager.thumb', [
            'disk' => 'local',
            'path' => 'icons/test.svg',
        ]));

        // THEN
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/svg+xml');

        ob_start();
        $response->sendContent();
        $output = (string) ob_get_clean();

        expect($output)->toBe($svgData);
    });

    it('returns 404 for non-existent file', function (): void {
        // GIVEN
        Storage::fake('local');

        // WHEN
        $response = $this->get(route('storage-manager.thumb', [
            'disk' => 'local',
            'path' => 'images/missing.png',
        ]));

        // THEN
        $response->assertStatus(404);
    });

    it('returns 404 for non-image file', function (): void {
        // GIVEN
        Storage::fake('local');
        Storage::disk('local')->put('docs/readme.txt', 'Hello world');

        // WHEN
        $response = $this->get(route('storage-manager.thumb', [
            'disk' => 'local',
            'path' => 'docs/readme.txt',
        ]));

        // THEN
        $response->assertStatus(404);
    });

    it('returns 404 when thumbnails are disabled in config', function (): void {
        // GIVEN
        Storage::fake('local');
        Config::set('storage-manager.thumbnails.enabled', false);
        app()->forgetInstance(StorageManagerConfig::class);

        $img = imagecreatetruecolor(10, 10);
        ob_start();
        imagepng($img);
        $pngData = (string) ob_get_clean();

        Storage::disk('local')->put('images/disabled.png', $pngData);

        // WHEN
        $response = $this->get(route('storage-manager.thumb', [
            'disk' => 'local',
            'path' => 'images/disabled.png',
        ]));

        Config::set('storage-manager.thumbnails.enabled', true);
        app()->forgetInstance(StorageManagerConfig::class);

        // THEN
        $response->assertStatus(404);
    });
});
