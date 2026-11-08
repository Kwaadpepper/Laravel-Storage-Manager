<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Acl\SmAbility;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Enum\UploadStatus;
use Kwaadpepper\LaravelStorageManager\Lib\Upload\UploadSessionService;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadId;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadMetadata;
use Tests\TestCase;

describe('UploadController', function (): void {
    beforeEach(function (): void {
        assert($this instanceof TestCase);
        Storage::fake('temp_disk');
        Storage::fake('local'); // Default disk
        Config::set('storage-manager.upload.temp_disk', 'temp_disk');
        Config::set('storage-manager.upload.temp_path', '/tmp/lsm_tests_uploads_' . uniqid());
        Config::set('storage-manager.auth.enabled', false);
    });

    it('can init an upload session', function (): void {
        assert($this instanceof TestCase);
        // GIVEN
        $payload = [
            'fileName'    => 'video.mp4',
            'totalSize'   => 1024 * 1024,
            'totalChunks' => 2,
        ];

        // WHEN
        $response = $this->postJson(route('storage-manager.api.fm.upload.init'), $payload);

        // THEN
        $response->assertOk()
            ->assertJsonStructure(['data' => ['upload_id']]);

        $uploadIdStr = $response->json('data.upload_id');
        expect($uploadIdStr)->toBeString();

        $uploadId       = new UploadId($uploadIdStr);
        $sessionService = resolve(UploadSessionService::class);
        expect($sessionService->sessionExists($uploadId))->toBeTrue();
    });

    it('rejects init with invalid data', function (): void {
        assert($this instanceof TestCase);
        // GIVEN
        $payload = [
            'fileName'    => '',
            'totalSize'   => -10,
            'totalChunks' => 0,
        ];

        // WHEN
        $response = $this->postJson(route('storage-manager.api.fm.upload.init'), $payload);

        // THEN
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['fileName', 'totalSize', 'totalChunks']);
    });

    it('can store a chunk', function (): void {
        assert($this instanceof TestCase);
        // GIVEN
        $uploadId       = UploadId::generate();
        $sessionService = resolve(UploadSessionService::class);
        $sessionService->createSession($uploadId, new UploadMetadata($uploadId, 'test.txt', 2, 100, CarbonImmutable::now()));

        $fileContent = str_repeat('a', 1024);
        $file        = UploadedFile::fake()->createWithContent('chunk.part', $fileContent);
        $checksum    = md5($fileContent);

        $payload = [
            'upload_id'      => $uploadId->value,
            'chunk_index'    => 0,
            'chunk_checksum' => $checksum,
            'file'           => $file,
        ];

        // WHEN
        $response = $this->postJson(route('storage-manager.api.fm.upload.chunk'), $payload);

        // THEN
        $response->assertOk()
            ->assertJson(['data' => ['success' => true]]);

        expect($sessionService->chunkExists($uploadId, 0))->toBeTrue();
    });

    it('rejects chunk with invalid checksum', function (): void {
        assert($this instanceof TestCase);
        // GIVEN
        $uploadId       = UploadId::generate();
        $sessionService = resolve(UploadSessionService::class);
        $sessionService->createSession($uploadId, new UploadMetadata($uploadId, 'test.txt', 2, 100, CarbonImmutable::now()));

        $fileContent = str_repeat('a', 1024);
        $file        = UploadedFile::fake()->createWithContent('chunk.part', $fileContent);

        $payload = [
            'upload_id'      => $uploadId->value,
            'chunk_index'    => 0,
            'chunk_checksum' => 'wrongchecksum',
            'file'           => $file,
        ];

        // WHEN
        $response = $this->postJson(route('storage-manager.api.fm.upload.chunk'), $payload);

        // THEN
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['chunk_checksum']);
    });

    it('can get upload status', function (): void {
        assert($this instanceof TestCase);
        // GIVEN
        $uploadId       = UploadId::generate();
        $sessionService = resolve(UploadSessionService::class);
        $sessionService->createSession($uploadId, new UploadMetadata($uploadId, 'test.txt', 2, 100, CarbonImmutable::now()));
        $sessionService->writeStatus($uploadId, UploadStatus::ASSEMBLING, 20);

        // WHEN
        $response = $this->getJson(route('storage-manager.api.fm.upload.status', ['uploadId' => $uploadId->value]));

        // THEN
        $response->assertOk()
            ->assertJson([
                'data' => [
                    'status'   => 'assembling',
                    'progress' => 20,
                ],
            ]);
    });

    it('returns waiting for non-existent status file', function (): void {
        assert($this instanceof TestCase);
        // GIVEN
        $uploadId       = UploadId::generate();
        $sessionService = resolve(UploadSessionService::class);
        $sessionService->createSession($uploadId, new UploadMetadata($uploadId, 'test.txt', 2, 100, CarbonImmutable::now()));

        // WHEN
        $response = $this->getJson(route('storage-manager.api.fm.upload.status', ['uploadId' => $uploadId->value]));

        // THEN
        $response->assertOk()
            ->assertJson([
                'data' => [
                    'status'   => 'waiting',
                    'progress' => 0,
                ],
            ]);
    });

    it('returns waiting for unknown upload id', function (): void {
        assert($this instanceof TestCase);
        // GIVEN
        $uploadId = UploadId::generate()->value;

        // WHEN
        $response = $this->getJson(route('storage-manager.api.fm.upload.status', ['uploadId' => $uploadId]));

        // THEN
        $response->assertOk()
            ->assertJson([
                'data' => [
                    'status'   => 'waiting',
                    'progress' => 0,
                ],
            ]);
    });

    it('can complete an upload', function (): void {
        assert($this instanceof TestCase);
        // GIVEN
        $uploadId       = UploadId::generate();
        $sessionService = resolve(UploadSessionService::class);
        $sessionService->createSession($uploadId, new UploadMetadata($uploadId, 'test.txt', 2, 11, CarbonImmutable::now()));

        $file1Content = str_repeat('a', 1024);
        $file1        = UploadedFile::fake()->createWithContent('chunk0.part', $file1Content);
        $sessionService->storeChunk($uploadId, 0, $file1);

        $file2Content = str_repeat('b', 1024);
        $file2        = UploadedFile::fake()->createWithContent('chunk1.part', $file2Content);
        $sessionService->storeChunk($uploadId, 1, $file2);

        $payload = [
            'upload_id'   => $uploadId->value,
            'fileName'    => 'test.txt',
            'totalChunks' => 2,
            'disk'        => 'local',
            'path'        => '/',
        ];

        // WHEN
        $response = $this->postJson(route('storage-manager.api.fm.upload.complete'), $payload);

        // THEN
        $response->assertOk();
        expect($response->headers->get('Content-Type'))->toContain('text/event-stream');

        $content = $response->streamedContent();
        expect($content)->toContain('data: ');
        expect($content)->toContain('"status":"completed"');
        expect($content)->toContain('"progress":100');
        expect($content)->toContain('"fileName":"test.txt"');

        expect(Storage::disk('local')->exists('test.txt'))->toBeTrue();
        expect(Storage::disk('local')->get('test.txt'))->toBe($file1Content . $file2Content);
    });

    it('emits error event via SSE when authorization fails on complete', function (): void {
        assert($this instanceof TestCase);
        // GIVEN
        $uploadId       = UploadId::generate();
        $sessionService = resolve(UploadSessionService::class);
        $sessionService->createSession($uploadId, new UploadMetadata($uploadId, 'test.txt', 1, 10, CarbonImmutable::now()));

        Gate::define(SmAbility::UPLOAD_COMPLETE, function (): bool {
            return false;
        });

        $payload = [
            'upload_id'   => $uploadId->value,
            'fileName'    => 'test.txt',
            'totalChunks' => 1,
            'disk'        => 'local',
            'path'        => '/',
        ];

        // WHEN
        $response = $this->postJson(route('storage-manager.api.fm.upload.complete'), $payload);

        // THEN
        $response->assertOk();
        $content = $response->streamedContent();
        expect($content)->toContain('"status":"error"');
    });

    it('sanitizes filename on complete', function (): void {
        assert($this instanceof TestCase);
        // GIVEN
        Config::set('storage-manager.upload.sanitize_filenames', true);
        app()->forgetInstance(StorageManagerConfig::class);

        $uploadId       = UploadId::generate();
        $sessionService = resolve(UploadSessionService::class);
        $sessionService->createSession($uploadId, new UploadMetadata($uploadId, 'test:file?.txt', 1, 10, CarbonImmutable::now()));

        $fileContent = str_repeat('a', 10);
        $file        = UploadedFile::fake()->createWithContent('chunk0.part', $fileContent);
        $sessionService->storeChunk($uploadId, 0, $file);

        $payload = [
            'upload_id'   => $uploadId->value,
            'fileName'    => 'test:file?.txt',
            'totalChunks' => 1,
            'disk'        => 'local',
            'path'        => '/',
        ];

        // WHEN
        $response = $this->postJson(route('storage-manager.api.fm.upload.complete'), $payload);

        // THEN
        $response->assertOk();

        $content = $response->streamedContent();
        expect($content)->toContain('"status":"completed"');
        expect($content)->toContain('"fileName":"test-file .txt"');
        expect(Storage::disk('local')->exists('test-file .txt'))->toBeTrue();
    });

    it('applies auto_rename duplicate policy on complete', function (): void {
        assert($this instanceof TestCase);
        // GIVEN
        Config::set('storage-manager.upload.duplicate_policy', 'auto_rename');
        app()->forgetInstance(StorageManagerConfig::class);
        Storage::disk('local')->put('test.txt', 'existing content');

        $uploadId       = UploadId::generate();
        $sessionService = resolve(UploadSessionService::class);
        $sessionService->createSession($uploadId, new UploadMetadata($uploadId, 'test.txt', 1, 10, CarbonImmutable::now()));

        $fileContent = str_repeat('a', 10);
        $file        = UploadedFile::fake()->createWithContent('chunk0.part', $fileContent);
        $sessionService->storeChunk($uploadId, 0, $file);

        $payload = [
            'upload_id'   => $uploadId->value,
            'fileName'    => 'test.txt',
            'totalChunks' => 1,
            'disk'        => 'local',
            'path'        => '/',
        ];

        // WHEN
        $response = $this->postJson(route('storage-manager.api.fm.upload.complete'), $payload);

        // THEN
        $response->assertOk();

        $content = $response->streamedContent();
        expect($content)->toContain('"status":"completed"');
        expect($content)->toContain('"fileName":"test (1).txt"');
        expect(Storage::disk('local')->exists('test (1).txt'))->toBeTrue();
        expect(Storage::disk('local')->get('test (1).txt'))->toBe($fileContent);
    });

    it('applies error duplicate policy on complete', function (): void {
        assert($this instanceof TestCase);
        // GIVEN
        Config::set('storage-manager.upload.duplicate_policy', 'error');
        app()->forgetInstance(StorageManagerConfig::class);
        Storage::disk('local')->put('test.txt', 'existing content');

        $uploadId       = UploadId::generate();
        $sessionService = resolve(UploadSessionService::class);
        $sessionService->createSession($uploadId, new UploadMetadata($uploadId, 'test.txt', 1, 10, CarbonImmutable::now()));

        $fileContent = str_repeat('a', 10);
        $file        = UploadedFile::fake()->createWithContent('chunk0.part', $fileContent);
        $sessionService->storeChunk($uploadId, 0, $file);

        $payload = [
            'upload_id'   => $uploadId->value,
            'fileName'    => 'test.txt',
            'totalChunks' => 1,
            'disk'        => 'local',
            'path'        => '/',
        ];

        // WHEN
        $response = $this->postJson(route('storage-manager.api.fm.upload.complete'), $payload);

        // THEN
        $response->assertOk();

        $content = $response->streamedContent();
        expect($content)->toContain('"status":"error"');
        expect($content)->toContain('The file already exists.');
        expect(Storage::disk('local')->get('test.txt'))->toBe('existing content');
    });

    it('applies overwrite duplicate policy on complete', function (): void {
        assert($this instanceof TestCase);
        // GIVEN
        Config::set('storage-manager.upload.duplicate_policy', 'overwrite');
        app()->forgetInstance(StorageManagerConfig::class);
        Storage::disk('local')->put('test.txt', 'existing content');

        $uploadId       = UploadId::generate();
        $sessionService = resolve(UploadSessionService::class);
        $sessionService->createSession($uploadId, new UploadMetadata($uploadId, 'test.txt', 1, 10, CarbonImmutable::now()));

        $fileContent = str_repeat('a', 10);
        $file        = UploadedFile::fake()->createWithContent('chunk0.part', $fileContent);
        $sessionService->storeChunk($uploadId, 0, $file);

        $payload = [
            'upload_id'   => $uploadId->value,
            'fileName'    => 'test.txt',
            'totalChunks' => 1,
            'disk'        => 'local',
            'path'        => '/',
        ];

        // WHEN
        $response = $this->postJson(route('storage-manager.api.fm.upload.complete'), $payload);

        // THEN
        $response->assertOk();

        $content = $response->streamedContent();
        expect($content)->toContain('"status":"completed"');
        expect($content)->toContain('"fileName":"test.txt"');
        expect(Storage::disk('local')->exists('test.txt'))->toBeTrue();
        expect(Storage::disk('local')->get('test.txt'))->toBe($fileContent);
    });

    it('rejects init when file extension is forbidden', function (): void {
        assert($this instanceof TestCase);
        // GIVEN
        $payload = [
            'fileName'    => 'shell.php',
            'totalSize'   => 1024,
            'totalChunks' => 1,
        ];

        // WHEN
        $response = $this->postJson(route('storage-manager.api.fm.upload.init'), $payload);

        // THEN
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['fileName']);
    });

    it('rejects complete and deletes session when assembled binary MIME does not match extension', function (): void {
        assert($this instanceof TestCase);
        // GIVEN
        $uploadId       = UploadId::generate();
        $sessionService = resolve(UploadSessionService::class);
        $sessionService->createSession($uploadId, new UploadMetadata($uploadId, 'spoofed.jpg', 1, 30, CarbonImmutable::now()));

        $phpPayload = "<?php echo 'hacked';";
        $file       = UploadedFile::fake()->createWithContent('chunk0.part', $phpPayload);
        $sessionService->storeChunk($uploadId, 0, $file);

        $payload = [
            'upload_id'   => $uploadId->value,
            'fileName'    => 'spoofed.jpg',
            'totalChunks' => 1,
            'disk'        => 'local',
            'path'        => '/',
        ];

        // WHEN
        $response = $this->postJson(route('storage-manager.api.fm.upload.complete'), $payload);

        // THEN
        $response->assertOk();

        $content = $response->streamedContent();
        expect($content)->toContain('"status":"error"');
        expect(Storage::disk('local')->exists('spoofed.jpg'))->toBeFalse();
        expect($sessionService->sessionExists($uploadId))->toBeFalse();
    });

    it('rejects init when file size exceeds disk max_file_size', function (): void {
        assert($this instanceof TestCase);
        // GIVEN
        Storage::fake('s3_limited');
        Config::set('filesystems.disks.s3_limited', ['driver' => 's3']);
        Config::set('storage-manager.disks.available', ['local', 's3_limited']);
        Config::set('storage-manager.disks.configs.s3_limited', [
            'max_file_size' => 500,
        ]);
        app()->forgetInstance(StorageManagerConfig::class);

        $payload = [
            'disk'        => 's3_limited',
            'fileName'    => 'doc.pdf',
            'totalSize'   => 600,
            'totalChunks' => 1,
        ];

        // WHEN
        $response = $this->postJson(route('storage-manager.api.fm.upload.init'), $payload);

        // THEN
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['totalSize']);
    });

    it('rejects init when file extension is forbidden by disk allowed_extensions', function (): void {
        assert($this instanceof TestCase);
        // GIVEN
        Storage::fake('s3_media');
        Config::set('filesystems.disks.s3_media', ['driver' => 's3']);
        Config::set('storage-manager.disks.available', ['local', 's3_media']);
        Config::set('storage-manager.disks.configs.s3_media', [
            'allowed_extensions' => ['jpg', 'png'],
        ]);
        app()->forgetInstance(StorageManagerConfig::class);

        $payload = [
            'disk'        => 's3_media',
            'fileName'    => 'video.mp4',
            'totalSize'   => 1024,
            'totalChunks' => 1,
        ];

        // WHEN
        $response = $this->postJson(route('storage-manager.api.fm.upload.init'), $payload);

        // THEN
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['fileName']);
    });

    it('rejects complete when file extension is forbidden by disk allowed_extensions', function (): void {
        assert($this instanceof TestCase);
        // GIVEN
        Storage::fake('s3_media');
        Config::set('filesystems.disks.s3_media', ['driver' => 's3']);
        Config::set('storage-manager.disks.available', ['local', 's3_media']);
        Config::set('storage-manager.disks.configs.s3_media', [
            'allowed_extensions' => ['jpg', 'png'],
        ]);
        app()->forgetInstance(StorageManagerConfig::class);

        $uploadId       = UploadId::generate();
        $sessionService = resolve(UploadSessionService::class);
        $sessionService->createSession($uploadId, new UploadMetadata($uploadId, 'allowed.jpg', 1, 10, CarbonImmutable::now()));

        $payload = [
            'upload_id'   => $uploadId->value,
            'fileName'    => 'archive.zip',
            'totalChunks' => 1,
            'disk'        => 's3_media',
            'path'        => '/',
        ];

        // WHEN
        $response = $this->postJson(route('storage-manager.api.fm.upload.complete'), $payload);

        // THEN
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['fileName']);
    });
});
