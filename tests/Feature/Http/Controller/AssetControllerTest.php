<?php

declare(strict_types=1);

use Kwaadpepper\LaravelStorageManager\Http\Controller\AssetController;

describe('AssetController', function (): void {
    it('serves existing static css and js assets with cache headers', function (): void {
        // GIVEN
        $cssUrl = route('storage-manager.assets', ['dir' => 'css', 'file' => 'main.css']);
        $jsUrl  = route('storage-manager.assets', ['dir' => 'js', 'file' => 'main.js']);

        // WHEN
        $cssResponse = $this->get($cssUrl);
        $jsResponse  = $jsUrl ? $this->get($jsUrl) : null;

        // THEN
        $cssResponse->assertStatus(200);
        $cssResponse->assertHeader('Cache-Control', 'max-age=31536000, public');
        if ($jsResponse !== null) {
            $jsResponse->assertStatus(200);
            $jsResponse->assertHeader('Cache-Control', 'max-age=31536000, public');
        }
    });

    it('returns 404 for invalid directory or non-existent file', function (): void {
        // GIVEN
        $invalidDirUrl      = route('storage-manager.assets', ['dir' => 'unknown_dir', 'file' => 'main.css']);
        $nonExistentFileUrl = route('storage-manager.assets', ['dir' => 'css', 'file' => 'does_not_exist.css']);

        // WHEN
        $invalidDirResponse      = $this->get($invalidDirUrl);
        $nonExistentFileResponse = $this->get($nonExistentFileUrl);

        // THEN
        $invalidDirResponse->assertStatus(404);
        $nonExistentFileResponse->assertStatus(404);
    });
})->covers(AssetController::class);
