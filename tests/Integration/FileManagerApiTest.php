<?php

declare(strict_types=1);

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Event\DirectoryRenamed;
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
    it('initializes the file manager', function (): void {
        assert($this instanceof TestCase);
        // GIVEN
        $route = route('storage-manager.api.fm.init');

        // WHEN
        $response = $this->getJson($route);

        // THEN
        $response->assertSuccessful()
            ->assertJsonPath('data.chunkMinSize', 2097152)
            ->assertJsonPath('data.chunkMaxSize', 2097152);
    });
});

describe('disk listing', function (): void {
    it('returns available disks', function (): void {
        assert($this instanceof TestCase);
        // GIVEN
        $route = route('storage-manager.api.disks.list');

        // WHEN
        $response = $this->getJson($route);

        // THEN
        $response->assertSuccessful()
            ->assertJsonPath('data.disks', ['local']);
    });
});

describe('content listing', function () use (&$disk): void {
    it('returns empty content on a fresh disk', function (): void {
        assert($this instanceof TestCase);
        // GIVEN
        $route = route('storage-manager.api.fm.content', [
            'path' => '/',
            'disk' => 'local',
        ]);

        // WHEN
        $response = $this->getJson($route);

        // THEN
        $response->assertSuccessful()
            ->assertJsonPath('data.files', [])
            ->assertJsonPath('data.directories', [])
            ->assertJsonPath('data.pagination.totalFiles', 0)
            ->assertJsonPath('data.pagination.hasMore', false);
    });

    it('lists files and directories after creation', function () use (&$disk): void {
        // GIVEN
        $disk->makeDirectory('photos');
        $disk->put('readme.md', '# Readme');
        $route = route('storage-manager.api.fm.content', [
            'path' => '/',
            'disk' => 'local',
        ]);

        // WHEN
        $response = $this->getJson($route);

        // THEN
        $response->assertSuccessful();
        $directories = $response->json('data.directories');
        $files       = $response->json('data.files');
        expect(array_column($directories, 'path'))->toContain('/photos');
        expect(array_column($files, 'path'))->toContain('/readme.md');
        expect($response->json('data.pagination.totalFiles'))->toBe(1);
        expect($response->json('data.pagination.hasMore'))->toBeFalse();
    });

    it('returns nested sub-directories and handles pagination', function () use (&$disk): void {
        // GIVEN
        $disk->makeDirectory('projects');
        $disk->makeDirectory('projects/laravel');
        $disk->put('file1.txt', '1');
        $disk->put('file2.txt', '2');
        $disk->put('file3.txt', '3');
        $route = route('storage-manager.api.fm.content', [
            'path'     => '/',
            'disk'     => 'local',
            'page'     => 1,
            'per_page' => 2,
        ]);

        // WHEN
        $response = $this->getJson($route);

        // THEN
        $response->assertSuccessful();
        $directories = $response->json('data.directories');
        $files       = $response->json('data.files');
        expect($directories)->toBeArray()->not->toBeEmpty();
        expect($directories[0]['path'])->toBe('/projects');
        expect($directories[0]['hasSubDirectories'])->toBeTrue();
        expect($files)->toHaveCount(2);
        expect($response->json('data.pagination.totalFiles'))->toBe(3);
        expect($response->json('data.pagination.totalPages'))->toBe(2);
        expect($response->json('data.pagination.hasMore'))->toBeTrue();
    });
});

describe('directory creation', function () use (&$disk): void {
    it('creates a new directory on disk', function () use (&$disk): void {
        // GIVEN
        $payload = ['path' => '/', 'name' => 'documents', 'disk' => 'local'];
        $route   = route('storage-manager.api.fm.create-directory');

        // WHEN
        $response = $this->postJson($route, $payload);

        // THEN
        $response->assertCreated();
        $disk->assertExists('documents');
    });

    it('rejects creating a directory that already exists', function () use (&$disk): void {
        // GIVEN
        $disk->makeDirectory('existing');
        $route = route('storage-manager.api.fm.create-directory');

        // WHEN
        $response = $this->postJson($route, [
            'path' => '/',
            'name' => 'existing',
            'disk' => 'local',
        ]);

        // THEN
        $response->assertUnprocessable();
    });
});

describe('file creation', function () use (&$disk): void {
    it('creates a new file with content', function () use (&$disk): void {
        // GIVEN
        $payload = [
            'path'    => '/',
            'name'    => 'hello.txt',
            'content' => 'Hello World',
            'disk'    => 'local',
        ];
        $route = route('storage-manager.api.fm.create-file');

        // WHEN
        $response = $this->postJson($route, $payload);

        // THEN
        $response->assertCreated();
        $disk->assertExists('hello.txt');
        expect($disk->get('hello.txt'))->toBe('Hello World');
    });

    it('rejects creating a file that already exists', function () use (&$disk): void {
        // GIVEN
        $disk->put('existing.txt', 'data');
        $route = route('storage-manager.api.fm.create-file');

        // WHEN
        $response = $this->postJson($route, [
            'path'    => '/',
            'name'    => 'existing.txt',
            'content' => '',
            'disk'    => 'local',
        ]);

        // THEN
        $response->assertUnprocessable();
    });

    it('rejects creating a file with a forbidden extension', function () use (&$disk): void {
        // GIVEN
        $route = route('storage-manager.api.fm.create-file');

        // WHEN
        $response = $this->postJson($route, [
            'path'    => '/',
            'name'    => 'shell.php',
            'content' => '<?php echo 1;',
            'disk'    => 'local',
        ]);

        // THEN
        $response->assertStatus(422);
        $disk->assertMissing('shell.php');
    });

    it('rejects creating a file whose content MIME conflicts with its extension', function () use (&$disk): void {
        // GIVEN
        $route = route('storage-manager.api.fm.create-file');

        // WHEN
        $response = $this->postJson($route, [
            'path'    => '/',
            'name'    => 'photo.jpg',
            'content' => '<?php echo "spoofed";',
            'disk'    => 'local',
        ]);

        // THEN
        $response->assertStatus(422);
        $disk->assertMissing('photo.jpg');
    });
});

describe('rename', function () use (&$disk): void {
    it('renames a file', function () use (&$disk): void {
        // GIVEN
        $disk->put('old-name.txt', 'content');
        $route = route('storage-manager.api.fm.rename');

        // WHEN
        $response = $this->putJson($route, [
            'path' => '/old-name.txt',
            'to'   => 'new-name.txt',
            'disk' => 'local',
        ]);

        // THEN
        $response->assertSuccessful();
        $disk->assertMissing('old-name.txt');
        $disk->assertExists('new-name.txt');
    });

    it('renames a directory and dispatches DirectoryRenamed', function () use (&$disk): void {
        // GIVEN
        Event::fake([DirectoryRenamed::class]);
        $disk->makeDirectory('old-folder');
        $route = route('storage-manager.api.fm.rename');

        // WHEN
        $response = $this->putJson($route, [
            'path' => '/old-folder',
            'to'   => 'new-folder',
            'disk' => 'local',
        ]);

        // THEN
        $response->assertSuccessful();
        $disk->assertMissing('old-folder');
        $disk->assertExists('new-folder');
        Event::assertDispatched(DirectoryRenamed::class);
    });

    it('rejects renaming a file to a forbidden extension', function () use (&$disk): void {
        // GIVEN
        $disk->put('note.txt', 'hello');
        $route = route('storage-manager.api.fm.rename');

        // WHEN
        $response = $this->putJson($route, [
            'path' => '/note.txt',
            'to'   => 'note.php',
            'disk' => 'local',
        ]);

        // THEN
        $response->assertStatus(422);
        $disk->assertExists('note.txt');
        $disk->assertMissing('note.php');
    });

    it('rejects renaming a file when new extension conflicts with its MIME content', function () use (&$disk): void {
        // GIVEN
        $disk->put('note.txt', 'plain text content');
        $route = route('storage-manager.api.fm.rename');

        // WHEN
        $response = $this->putJson($route, [
            'path' => '/note.txt',
            'to'   => 'note.jpg',
            'disk' => 'local',
        ]);

        // THEN
        $response->assertStatus(422);
        $disk->assertExists('note.txt');
        $disk->assertMissing('note.jpg');
    });
});

describe('delete', function () use (&$disk): void {
    it('deletes a file permanently with force', function () use (&$disk): void {
        // GIVEN
        $disk->put('to-delete.txt', 'bye');
        $route = route('storage-manager.api.fm.delete');

        // WHEN
        $response = $this->deleteJson($route, [
            'path'  => '/to-delete.txt',
            'disk'  => 'local',
            'force' => true,
        ]);

        // THEN
        $response->assertNoContent();
        $disk->assertMissing('to-delete.txt');
    });

    it('soft deletes a file to trash by default', function () use (&$disk): void {
        // GIVEN
        $disk->put('to-soft-delete.txt', 'bye');
        $route = route('storage-manager.api.fm.delete');

        // WHEN
        $response = $this->deleteJson($route, [
            'path' => '/to-soft-delete.txt',
            'disk' => 'local',
        ]);

        // THEN
        $response->assertOk()
            ->assertJsonPath('data.trashed', true);
        $disk->assertMissing('to-soft-delete.txt');
    });

    it('deletes a directory with its contents with force', function () use (&$disk): void {
        // GIVEN
        $disk->makeDirectory('to-remove');
        $disk->put('to-remove/file.txt', 'data');
        $route = route('storage-manager.api.fm.delete');

        // WHEN
        $response = $this->deleteJson($route, [
            'path'  => '/to-remove',
            'disk'  => 'local',
            'force' => true,
        ]);

        // THEN
        $response->assertNoContent();
        $disk->assertMissing('to-remove');
    });

    it('soft deletes a directory to trash by default', function () use (&$disk): void {
        // GIVEN
        $disk->makeDirectory('to-soft-remove');
        $disk->put('to-soft-remove/file.txt', 'data');
        $route = route('storage-manager.api.fm.delete');

        // WHEN
        $response = $this->deleteJson($route, [
            'path' => '/to-soft-remove',
            'disk' => 'local',
        ]);

        // THEN
        $response->assertOk()
            ->assertJsonPath('data.trashed', true);
        $disk->assertMissing('to-soft-remove');
    });

    it('rejects deleting a non-empty directory without force when trash is disabled', function () use (&$disk): void {
        // GIVEN
        Config::set('storage-manager.trash.enabled', false);
        app()->forgetInstance(StorageManagerConfig::class);
        $disk->makeDirectory('to-reject');
        $disk->put('to-reject/file.txt', 'data');
        $route = route('storage-manager.api.fm.delete');

        // WHEN
        $response = $this->deleteJson($route, [
            'path' => '/to-reject',
            'disk' => 'local',
        ]);

        // THEN
        $response->assertStatus(422)
            ->assertJsonPath('errors.message', 'The directory is not empty.');
    });
});

describe('copy', function () use (&$disk): void {
    it('copies a file', function () use (&$disk): void {
        // GIVEN
        $disk->put('source.txt', 'content');
        $disk->makeDirectory('dest');
        $route = route('storage-manager.api.fm.copy');

        // WHEN
        $response = $this->postJson($route, [
            'path'            => '/source.txt',
            'destination_dir' => '/dest',
            'disk'            => 'local',
        ]);

        // THEN
        $response->assertSuccessful();
        $disk->assertExists('source.txt');
        $disk->assertExists('dest/source.txt');
    });

    it('copies a directory', function () use (&$disk): void {
        // GIVEN
        $disk->makeDirectory('source-dir');
        $disk->put('source-dir/file.txt', 'content');
        $disk->makeDirectory('dest-dir');
        $route = route('storage-manager.api.fm.copy');

        // WHEN
        $response = $this->postJson($route, [
            'path'            => '/source-dir',
            'destination_dir' => '/dest-dir',
            'disk'            => 'local',
        ]);

        // THEN
        $response->assertSuccessful();
        $disk->assertExists('source-dir');
        $disk->assertExists('dest-dir/source-dir');
        $disk->assertExists('dest-dir/source-dir/file.txt');
    });
});

describe('move', function () use (&$disk): void {
    it('moves a file', function () use (&$disk): void {
        // GIVEN
        $disk->put('source.txt', 'content');
        $disk->makeDirectory('dest');
        $route = route('storage-manager.api.fm.move');

        // WHEN
        $response = $this->postJson($route, [
            'path'            => '/source.txt',
            'destination_dir' => '/dest',
            'disk'            => 'local',
        ]);

        // THEN
        $response->assertSuccessful();
        $disk->assertMissing('source.txt');
        $disk->assertExists('dest/source.txt');
    });

    it('moves a directory', function () use (&$disk): void {
        // GIVEN
        $disk->makeDirectory('source-dir');
        $disk->put('source-dir/file.txt', 'content');
        $disk->makeDirectory('dest-dir');
        $route = route('storage-manager.api.fm.move');

        // WHEN
        $response = $this->postJson($route, [
            'path'            => '/source-dir',
            'destination_dir' => '/dest-dir',
            'disk'            => 'local',
        ]);

        // THEN
        $response->assertSuccessful();
        $disk->assertMissing('source-dir');
        $disk->assertExists('dest-dir/source-dir');
        $disk->assertExists('dest-dir/source-dir/file.txt');
    });
});

describe('validation', function (): void {
    it('returns 422 when required fields are missing', function (): void {
        assert($this instanceof TestCase);
        // GIVEN
        $emptyPayload = [];
        $route        = route('storage-manager.api.fm.create-directory');

        // WHEN
        $response = $this->postJson($route, $emptyPayload);

        // THEN
        $response->assertUnprocessable();
    });
});
