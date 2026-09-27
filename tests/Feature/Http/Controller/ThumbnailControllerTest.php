<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controller;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Repository\ConfigRepository;

describe('ThumbnailController', function (): void {

    it('generates a webp thumbnail for a png image', function (): void {
        Storage::fake('local');

        $img = imagecreatetruecolor(20, 20);
        ob_start();
        imagepng($img);
        $pngData = (string) ob_get_clean();

        Storage::disk('local')->put('images/test.png', $pngData);

        $response = $this->get(route('storage-manager.thumb', [
            'disk' => 'local',
            'path' => 'images/test.png',
        ]));

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

        $secondResponse = $this->get(
            route('storage-manager.thumb', [
                'disk' => 'local',
                'path' => 'images/test2.png',
            ]),
            ['If-None-Match' => (string) $etag]
        );

        $secondResponse->assertStatus(304);
        $secondResponse->assertHeader('ETag', (string) $etag);
    });

    it('serves svg thumbnail directly with image/svg+xml content-type', function (): void {
        Storage::fake('local');

        $svgData = '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><circle cx="5" cy="5" r="5"/></svg>';
        Storage::disk('local')->put('icons/test.svg', $svgData);

        $response = $this->get(route('storage-manager.thumb', [
            'disk' => 'local',
            'path' => 'icons/test.svg',
        ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/svg+xml');

        ob_start();
        $response->sendContent();
        $output = (string) ob_get_clean();

        expect($output)->toBe($svgData);
    });

    it('returns 404 for non-existent file', function (): void {
        Storage::fake('local');

        $response = $this->get(route('storage-manager.thumb', [
            'disk' => 'local',
            'path' => 'images/missing.png',
        ]));

        $response->assertStatus(404);
    });

    it('returns 404 for non-image file', function (): void {
        Storage::fake('local');
        Storage::disk('local')->put('docs/readme.txt', 'Hello world');

        $response = $this->get(route('storage-manager.thumb', [
            'disk' => 'local',
            'path' => 'docs/readme.txt',
        ]));

        $response->assertStatus(404);
    });

    it('returns 404 when thumbnails are disabled in config', function (): void {
        Storage::fake('local');
        Config::set('storage-manager.thumbnails.enabled', false);
        app()->forgetInstance(ConfigRepository::class);

        $img = imagecreatetruecolor(10, 10);
        ob_start();
        imagepng($img);
        $pngData = (string) ob_get_clean();

        Storage::disk('local')->put('images/disabled.png', $pngData);

        $response = $this->get(route('storage-manager.thumb', [
            'disk' => 'local',
            'path' => 'images/disabled.png',
        ]));

        Config::set('storage-manager.thumbnails.enabled', true);
        app()->forgetInstance(ConfigRepository::class);

        $response->assertStatus(404);
    });
});
