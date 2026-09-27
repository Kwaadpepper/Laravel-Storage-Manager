<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Event\ArchiveExtracted;
use Kwaadpepper\LaravelStorageManager\Event\ZipCreated;

describe('ZipController', function () {
    it('can zip files and folders into an archive', function () {
        Event::fake([ZipCreated::class]);
        Storage::fake('local');
        Storage::disk('local')->put('archive_test/doc.txt', 'Document content');
        Storage::disk('local')->put('archive_test/sub/notes.md', 'Notes content');

        $response = $this->postJson(route('storage-manager.api.fm.zip'), [
            'disk'        => 'local',
            'paths'       => ['/archive_test/doc.txt', '/archive_test/sub'],
            'destination' => '/my_archive.zip',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'path',
                ],
            ]);

        expect(Storage::disk('local')->exists('my_archive.zip'))->toBeTrue();
        Event::assertDispatched(ZipCreated::class);
    });

    it('can unzip an archive into a destination directory', function () {
        Event::fake([ArchiveExtracted::class]);
        Storage::fake('local');

        // Create a real zip file in storage
        $tempZip = tempnam(sys_get_temp_dir(), 'test_zip_');
        assert(is_string($tempZip));
        $zip = new \ZipArchive();
        $zip->open($tempZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('hello.txt', 'Hello extracted world!');
        $zip->addFromString('nested/inner.txt', 'Inner file content');
        $zip->close();

        Storage::disk('local')->put('test.zip', (string) file_get_contents($tempZip));
        @unlink($tempZip);

        $response = $this->postJson(route('storage-manager.api.fm.unzip'), [
            'disk'           => 'local',
            'path'           => '/test.zip',
            'destinationDir' => '/extracted_here',
        ]);

        $response->assertStatus(200);

        expect(Storage::disk('local')->exists('extracted_here/hello.txt'))->toBeTrue();
        expect(Storage::disk('local')->get('extracted_here/hello.txt'))->toBe('Hello extracted world!');
        expect(Storage::disk('local')->exists('extracted_here/nested/inner.txt'))->toBeTrue();

        Event::assertDispatched(ArchiveExtracted::class);
    });

    it('rejects zip slip attempt during extraction', function () {
        Storage::fake('local');

        // Create a zip with malicious traversal entry
        $tempZip = tempnam(sys_get_temp_dir(), 'test_slip_');
        assert(is_string($tempZip));
        $zip = new \ZipArchive();
        $zip->open($tempZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('../../../etc/evil.txt', 'evil');
        $zip->close();

        Storage::disk('local')->put('malicious.zip', (string) file_get_contents($tempZip));
        @unlink($tempZip);

        $response = $this->postJson(route('storage-manager.api.fm.unzip'), [
            'disk'           => 'local',
            'path'           => '/malicious.zip',
            'destinationDir' => '/safe_folder',
        ]);

        $response->assertStatus(422);
    });

    it('rejects zip when destination already exists', function () {
        Storage::fake('local');
        Storage::disk('local')->put('existing.zip', 'already here');
        Storage::disk('local')->put('file.txt', 'content');

        $response = $this->postJson(route('storage-manager.api.fm.zip'), [
            'disk'        => 'local',
            'paths'       => ['/file.txt'],
            'destination' => '/existing.zip',
        ]);

        $response->assertStatus(422);
    });
});
