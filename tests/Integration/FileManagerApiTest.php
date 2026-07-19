<?php

declare(strict_types=1);

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

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
    assert($this instanceof TestCase);
    it('initializes the file manager', function (): void {
        assert($this instanceof TestCase);
        // Given
        $route = route('storage-manager.api.fm.init');

        // When
        $response = $this->getJson($route);

        // Then
        $response->assertSuccessful()
            ->assertJsonPath('data.chunkMinSize', 2097152)
            ->assertJsonPath('data.chunkMaxSize', 2097152);
    });
});

describe('disk listing', function (): void {
    assert($this instanceof TestCase);
    it('returns available disks', function (): void {
        assert($this instanceof TestCase);
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
        assert($this instanceof TestCase);
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
        $route = route('storage-manager.api.fm.content', [
            'path' => '/',
            'disk' => 'local',
        ]);

        // When
        $response = $this->getJson($route);

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
        $route = route('storage-manager.api.fm.tree', [
            'path' => '/',
            'disk' => 'local',
        ]);

        // When
        $response = $this->getJson($route);

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
        $route   = route('storage-manager.api.fm.create-directory');

        // When
        $response = $this->postJson($route, $payload);

        // Then
        $response->assertCreated();
        $disk->assertExists('documents');
    });

    it('rejects creating a directory that already exists', function () use (&$disk): void {
        // Given
        $disk->makeDirectory('existing');
        $route = route('storage-manager.api.fm.create-directory');

        // When
        $response = $this->postJson($route, [
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
        $route = route('storage-manager.api.fm.create-file');

        // When
        $response = $this->postJson($route, $payload);

        // Then
        $response->assertCreated();
        $disk->assertExists('hello.txt');
        expect($disk->get('hello.txt'))->toBe('Hello World');
    });

    it('rejects creating a file that already exists', function () use (&$disk): void {
        // Given
        $disk->put('existing.txt', 'data');
        $route = route('storage-manager.api.fm.create-file');

        // When
        $response = $this->postJson($route, [
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
        $route = route('storage-manager.api.fm.rename');

        // When
        $response = $this->putJson($route, [
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
        $route = route('storage-manager.api.fm.rename');

        // When
        $response = $this->putJson($route, [
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
        $route = route('storage-manager.api.fm.delete');

        // When
        $response = $this->deleteJson($route, [
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
        $route = route('storage-manager.api.fm.delete');

        // When
        $response = $this->deleteJson($route, [
            'path'  => '/to-remove',
            'disk'  => 'local',
            'force' => true,
        ]);

        // Then
        $response->assertNoContent();
        $disk->assertMissing('to-remove');
    });

    it('rejects deleting a non-empty directory without force', function () use (&$disk): void {
        // Given
        $disk->makeDirectory('to-reject');
        $disk->put('to-reject/file.txt', 'data');
        $route = route('storage-manager.api.fm.delete');

        // When
        $response = $this->deleteJson($route, [
            'path' => '/to-reject',
            'disk' => 'local',
        ]);

        // Then
        $response->assertStatus(422)
            ->assertJsonPath('errors.message', 'The directory is not empty.');
    });
});

describe('copy', function () use (&$disk): void {
    it('copies a file', function () use (&$disk): void {
        // Given
        $disk->put('source.txt', 'content');
        $disk->makeDirectory('dest');
        $route = route('storage-manager.api.fm.copy');

        // When
        $response = $this->postJson($route, [
            'path'            => '/source.txt',
            'destination_dir' => '/dest',
            'disk'            => 'local',
        ]);

        // Then
        $response->assertSuccessful();
        $disk->assertExists('source.txt');
        $disk->assertExists('dest/source.txt');
    });

    it('copies a directory', function () use (&$disk): void {
        // Given
        $disk->makeDirectory('source-dir');
        $disk->put('source-dir/file.txt', 'content');
        $disk->makeDirectory('dest-dir');
        $route = route('storage-manager.api.fm.copy');

        // When
        $response = $this->postJson($route, [
            'path'            => '/source-dir',
            'destination_dir' => '/dest-dir',
            'disk'            => 'local',
        ]);

        // Then
        $response->assertSuccessful();
        $disk->assertExists('source-dir');
        $disk->assertExists('dest-dir/source-dir');
        $disk->assertExists('dest-dir/source-dir/file.txt');
    });
});

describe('move', function () use (&$disk): void {
    it('moves a file', function () use (&$disk): void {
        // Given
        $disk->put('source.txt', 'content');
        $disk->makeDirectory('dest');
        $route = route('storage-manager.api.fm.move');

        // When
        $response = $this->postJson($route, [
            'path'            => '/source.txt',
            'destination_dir' => '/dest',
            'disk'            => 'local',
        ]);

        // Then
        $response->assertSuccessful();
        $disk->assertMissing('source.txt');
        $disk->assertExists('dest/source.txt');
    });

    it('moves a directory', function () use (&$disk): void {
        // Given
        $disk->makeDirectory('source-dir');
        $disk->put('source-dir/file.txt', 'content');
        $disk->makeDirectory('dest-dir');
        $route = route('storage-manager.api.fm.move');

        // When
        $response = $this->postJson($route, [
            'path'            => '/source-dir',
            'destination_dir' => '/dest-dir',
            'disk'            => 'local',
        ]);

        // Then
        $response->assertSuccessful();
        $disk->assertMissing('source-dir');
        $disk->assertExists('dest-dir/source-dir');
        $disk->assertExists('dest-dir/source-dir/file.txt');
    });
});

describe('validation', function (): void {
    assert($this instanceof TestCase);
    it('returns 422 when required fields are missing', function (): void {
        assert($this instanceof TestCase);
        // Given
        $emptyPayload = [];
        $route        = route('storage-manager.api.fm.create-directory');

        // When
        $response = $this->postJson($route, $emptyPayload);

        // Then
        $response->assertUnprocessable();
    });
});
