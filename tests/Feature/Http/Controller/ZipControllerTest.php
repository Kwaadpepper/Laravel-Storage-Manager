<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Event\ArchiveExtracted;
use Kwaadpepper\LaravelStorageManager\Event\ZipCreated;

describe('ZipController', function (): void {
    it('can zip files and folders into an archive', function (): void {
        // GIVEN
        Event::fake([ZipCreated::class]);
        Storage::fake('local');
        Storage::disk('local')->put('archive_test/doc.txt', 'Document content');
        Storage::disk('local')->put('archive_test/sub/notes.md', 'Notes content');

        // WHEN
        $response = $this->postJson(route('storage-manager.api.fm.zip'), [
            'disk'        => 'local',
            'paths'       => ['/archive_test/doc.txt', '/archive_test/sub'],
            'destination' => '/my_archive.zip',
        ]);

        // THEN
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'path',
                ],
            ]);

        expect(Storage::disk('local')->exists('my_archive.zip'))->toBeTrue();
        Event::assertDispatched(ZipCreated::class);
    });

    it('can unzip an archive into a destination directory', function (): void {
        // GIVEN
        Event::fake([ArchiveExtracted::class]);
        Storage::fake('local');

        $tempZip = tempnam(sys_get_temp_dir(), 'test_zip_');
        assert(is_string($tempZip));
        try {
            $zip = new \ZipArchive();
            $zip->open($tempZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
            $zip->addFromString('hello.txt', 'Hello extracted world!');
            $zip->addFromString('nested/inner.txt', 'Inner file content');
            $zip->close();

            Storage::disk('local')->put('test.zip', (string) file_get_contents($tempZip));
        } finally {
            if (file_exists($tempZip)) {
                @unlink($tempZip);
            }
        }

        // WHEN
        $response = $this->postJson(route('storage-manager.api.fm.unzip'), [
            'disk'           => 'local',
            'path'           => '/test.zip',
            'destinationDir' => '/extracted_here',
        ]);

        // THEN
        $response->assertStatus(200);

        expect(Storage::disk('local')->exists('extracted_here/hello.txt'))->toBeTrue();
        expect(Storage::disk('local')->get('extracted_here/hello.txt'))->toBe('Hello extracted world!');
        expect(Storage::disk('local')->exists('extracted_here/nested/inner.txt'))->toBeTrue();

        Event::assertDispatched(ArchiveExtracted::class);
    });

    it('rejects zip slip attempt during extraction', function (): void {
        // GIVEN
        Storage::fake('local');

        $tempZip = tempnam(sys_get_temp_dir(), 'test_slip_');
        assert(is_string($tempZip));
        try {
            $zip = new \ZipArchive();
            $zip->open($tempZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
            $zip->addFromString('../../../etc/evil.txt', 'evil');
            $zip->close();

            Storage::disk('local')->put('malicious.zip', (string) file_get_contents($tempZip));
        } finally {
            if (file_exists($tempZip)) {
                @unlink($tempZip);
            }
        }

        // WHEN
        $response = $this->postJson(route('storage-manager.api.fm.unzip'), [
            'disk'           => 'local',
            'path'           => '/malicious.zip',
            'destinationDir' => '/safe_folder',
        ]);

        // THEN
        $response->assertStatus(422);
    });

    it('rejects zip when destination already exists', function (): void {
        // GIVEN
        Storage::fake('local');
        Storage::disk('local')->put('existing.zip', 'already here');
        Storage::disk('local')->put('file.txt', 'content');

        // WHEN
        $response = $this->postJson(route('storage-manager.api.fm.zip'), [
            'disk'        => 'local',
            'paths'       => ['/file.txt'],
            'destination' => '/existing.zip',
        ]);

        // THEN
        $response->assertStatus(422);
    });

    it('rejects unzip when archive contains a forbidden extension', function (): void {
        // GIVEN
        Storage::fake('local');

        $tempZip = tempnam(sys_get_temp_dir(), 'test_ext_');
        assert(is_string($tempZip));
        try {
            $zip = new \ZipArchive();
            $zip->open($tempZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
            $zip->addFromString('safe.txt', 'hello');
            $zip->addFromString('backdoor.php', '<?php system($_GET["cmd"]);');
            $zip->close();

            Storage::disk('local')->put('forbidden_ext.zip', (string) file_get_contents($tempZip));
        } finally {
            if (file_exists($tempZip)) {
                @unlink($tempZip);
            }
        }

        // WHEN
        $response = $this->postJson(route('storage-manager.api.fm.unzip'), [
            'disk'           => 'local',
            'path'           => '/forbidden_ext.zip',
            'destinationDir' => '/extracted_ext',
        ]);

        // THEN
        $response->assertStatus(422);
        expect(Storage::disk('local')->exists('extracted_ext/safe.txt'))->toBeFalse();
        expect(Storage::disk('local')->exists('extracted_ext/backdoor.php'))->toBeFalse();
    });

    it('rejects unzip when archive contains a file with spoofed MIME content', function (): void {
        // GIVEN
        Storage::fake('local');

        $tempZip = tempnam(sys_get_temp_dir(), 'test_mime_');
        assert(is_string($tempZip));
        try {
            $zip = new \ZipArchive();
            $zip->open($tempZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
            $zip->addFromString('fake_photo.jpg', '<?php echo "not an image";');
            $zip->close();

            Storage::disk('local')->put('spoofed_mime.zip', (string) file_get_contents($tempZip));
        } finally {
            if (file_exists($tempZip)) {
                @unlink($tempZip);
            }
        }

        // WHEN
        $response = $this->postJson(route('storage-manager.api.fm.unzip'), [
            'disk'           => 'local',
            'path'           => '/spoofed_mime.zip',
            'destinationDir' => '/extracted_mime',
        ]);

        // THEN
        $response->assertStatus(422);
        expect(Storage::disk('local')->exists('extracted_mime/fake_photo.jpg'))->toBeFalse();
    });
});
