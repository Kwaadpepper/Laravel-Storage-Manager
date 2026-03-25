<?php

declare(strict_types=1);

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

/** @var FilesystemAdapter $disk */
$disk = null;

beforeEach(function () use (&$disk): void {
    $disk = Storage::fake('local');

    $this->app['config']->set('storage-manager.enabled', true);
    $this->app['config']->set('storage-manager.auth.enabled', false);
    $this->app['config']->set('storage-manager.disks.default', 'local');
    $this->app['config']->set('storage-manager.disks.available', ['local']);
});

describe('initialization', function (): void {
    it('initializes the file manager', function (): void {
        // Given
        $route = route('storage-manager.api.fm.init');

        // When
        $response = $this->getJson($route);

        // Then
        $response->assertNoContent();
    });
});

describe('disk listing', function (): void {
    it('returns available disks', function (): void {
        // Given
        $route = route('storage-manager.api.disks.list');

        // When
        $response = $this->getJson($route);

        // Then
        $response->assertSuccessful()
            ->assertJsonPath('data.disks', ['local']);
    });
});

describe('content listing', function () use (&$disk): void {
    it('returns empty content on a fresh disk', function (): void {
        // Given
        $route = route('storage-manager.api.fm.content', [
            'path' => '/',
            'disk' => 'local',
        ]);

        // When
        $response = $this->getJson($route);

        // Then
        $response->assertSuccessful()
            ->assertJsonPath('data.files', [])
            ->assertJsonPath('data.directories', []);
    });

    it('lists files and directories after creation', function () use (&$disk): void {
        // Given
        $disk->makeDirectory('photos');
        $disk->put('readme.md', '# Readme');

        // When
        $response = $this->getJson(route('storage-manager.api.fm.content', [
            'path' => '/',
            'disk' => 'local',
        ]));

        // Then
        $response->assertSuccessful();
        $data = $response->json('data');
        expect($data['directories'])->toContain('/photos');
        expect($data['files'])->toContain('/readme.md');
    });

    it('returns directory tree with nested sub-directories', function () use (&$disk): void {
        // Given
        $disk->makeDirectory('projects');
        $disk->makeDirectory('projects/laravel');

        // When
        $response = $this->getJson(route('storage-manager.api.fm.tree', [
            'path' => '/',
            'disk' => 'local',
        ]));

        // Then
        $response->assertSuccessful();
        $directories = $response->json('data.directories');
        expect($directories)->toBeArray()->not->toBeEmpty();
        expect($directories[0]['path'])->toBe('/projects');
        expect($directories[0]['hasSubDirectories'])->toBeTrue();
    });
});

describe('directory creation', function () use (&$disk): void {
    it('creates a new directory on disk', function () use (&$disk): void {
        // Given
        $payload = ['path' => '/', 'name' => 'documents', 'disk' => 'local'];

        // When
        $response = $this->postJson(route('storage-manager.api.fm.create-directory'), $payload);

        // Then
        $response->assertCreated();
        $disk->assertExists('documents');
    });

    it('rejects creating a directory that already exists', function () use (&$disk): void {
        // Given
        $disk->makeDirectory('existing');

        // When
        $response = $this->postJson(route('storage-manager.api.fm.create-directory'), [
            'path' => '/',
            'name' => 'existing',
            'disk' => 'local',
        ]);

        // Then
        $response->assertUnprocessable();
    });
});

describe('file creation', function () use (&$disk): void {
    it('creates a new file with content', function () use (&$disk): void {
        // Given
        $payload = [
            'path'    => '/',
            'name'    => 'hello.txt',
            'content' => 'Hello World',
            'disk'    => 'local',
        ];

        // When
        $response = $this->postJson(route('storage-manager.api.fm.create-file'), $payload);

        // Then
        $response->assertCreated();
        $disk->assertExists('hello.txt');
        expect($disk->get('hello.txt'))->toBe('Hello World');
    });

    it('rejects creating a file that already exists', function () use (&$disk): void {
        // Given
        $disk->put('existing.txt', 'data');

        // When
        $response = $this->postJson(route('storage-manager.api.fm.create-file'), [
            'path'    => '/',
            'name'    => 'existing.txt',
            'content' => '',
            'disk'    => 'local',
        ]);

        // Then
        $response->assertUnprocessable();
    });
});

describe('rename', function () use (&$disk): void {
    it('renames a file', function () use (&$disk): void {
        // Given
        $disk->put('old-name.txt', 'content');

        // When
        $response = $this->putJson(route('storage-manager.api.fm.rename'), [
            'path' => '/old-name.txt',
            'to'   => 'new-name.txt',
            'disk' => 'local',
        ]);

        // Then
        $response->assertSuccessful();
        $disk->assertMissing('old-name.txt');
        $disk->assertExists('new-name.txt');
    });

    it('renames a directory', function () use (&$disk): void {
        // Given
        $disk->makeDirectory('old-folder');

        // When
        $response = $this->putJson(route('storage-manager.api.fm.rename'), [
            'path' => '/old-folder',
            'to'   => 'new-folder',
            'disk' => 'local',
        ]);

        // Then
        $response->assertSuccessful();
        $disk->assertMissing('old-folder');
        $disk->assertExists('new-folder');
    });
});

describe('delete', function () use (&$disk): void {
    it('deletes a file', function () use (&$disk): void {
        // Given
        $disk->put('to-delete.txt', 'bye');

        // When
        $response = $this->deleteJson(route('storage-manager.api.fm.delete'), [
            'path' => '/to-delete.txt',
            'disk' => 'local',
        ]);

        // Then
        $response->assertNoContent();
        $disk->assertMissing('to-delete.txt');
    });

    it('deletes a directory with its contents', function () use (&$disk): void {
        // Given
        $disk->makeDirectory('to-remove');
        $disk->put('to-remove/file.txt', 'data');

        // When
        $response = $this->deleteJson(route('storage-manager.api.fm.delete'), [
            'path' => '/to-remove',
            'disk' => 'local',
        ]);

        // Then
        $response->assertNoContent();
        $disk->assertMissing('to-remove');
    });
});

describe('validation', function (): void {
    it('returns 422 when required fields are missing', function (): void {
        // Given
        $emptyPayload = [];

        // When
        $response = $this->postJson(route('storage-manager.api.fm.create-directory'), $emptyPayload);

        // Then
        $response->assertUnprocessable();
    });
});
