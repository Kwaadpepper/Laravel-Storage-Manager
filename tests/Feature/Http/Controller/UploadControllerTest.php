<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Enum\UploadStatus;
use Kwaadpepper\LaravelStorageManager\Lib\Upload\UploadSessionService;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadId;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadMetadata;
use Kwaadpepper\LaravelStorageManager\Repository\ConfigRepository;
use Tests\TestCase;

describe('UploadController', function (): void {
    assert($this instanceof TestCase);
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
        // Given
        $payload = [
            'fileName'    => 'video.mp4',
            'totalSize'   => 1024 * 1024,
            'totalChunks' => 2,
        ];

        // When
        $response = $this->postJson(route('storage-manager.api.fm.upload.init'), $payload);

        // Then
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
        // Given
        $payload = [
            'fileName'    => '',
            'totalSize'   => -10,
            'totalChunks' => 0,
        ];

        // When
        $response = $this->postJson(route('storage-manager.api.fm.upload.init'), $payload);

        // Then
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['fileName', 'totalSize', 'totalChunks']);
    });

    it('can store a chunk', function (): void {
        assert($this instanceof TestCase);
        // Given
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

        // When
        $response = $this->postJson(route('storage-manager.api.fm.upload.chunk'), $payload);

        // Then
        $response->assertOk()
            ->assertJson(['data' => ['success' => true]]);

        expect($sessionService->chunkExists($uploadId, 0))->toBeTrue();
    });

    it('rejects chunk with invalid checksum', function (): void {
        assert($this instanceof TestCase);
        // Given
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

        // When
        $response = $this->postJson(route('storage-manager.api.fm.upload.chunk'), $payload);

        // Then
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['chunk_checksum']);
    });

    it('can get upload status', function (): void {
        assert($this instanceof TestCase);
        // Given
        $uploadId       = UploadId::generate();
        $sessionService = resolve(UploadSessionService::class);
        $sessionService->createSession($uploadId, new UploadMetadata($uploadId, 'test.txt', 2, 100, CarbonImmutable::now()));
        $sessionService->writeStatus($uploadId, UploadStatus::ASSEMBLING, 20);

        // When
        $response = $this->getJson(route('storage-manager.api.fm.upload.status', ['uploadId' => $uploadId->value]));

        // Then
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
        // Given
        $uploadId       = UploadId::generate();
        $sessionService = resolve(UploadSessionService::class);
        $sessionService->createSession($uploadId, new UploadMetadata($uploadId, 'test.txt', 2, 100, CarbonImmutable::now()));

        // When
        $response = $this->getJson(route('storage-manager.api.fm.upload.status', ['uploadId' => $uploadId->value]));

        // Then
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
        // Given
        $uploadId = UploadId::generate()->value;

        // When
        $response = $this->getJson(route('storage-manager.api.fm.upload.status', ['uploadId' => $uploadId]));

        // Then
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
        // Given
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

        // When
        $response = $this->postJson(route('storage-manager.api.fm.upload.complete'), $payload);

        // Then
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

    it('sanitizes filename on complete', function (): void {
        assert($this instanceof TestCase);
        Config::set('storage-manager.upload.sanitize_filenames', true);
        app()->forgetInstance(ConfigRepository::class);

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

        $response = $this->postJson(route('storage-manager.api.fm.upload.complete'), $payload);
        $response->assertOk();

        $content = $response->streamedContent();
        expect($content)->toContain('"status":"completed"');
        expect($content)->toContain('"fileName":"test file .txt"');
        expect(Storage::disk('local')->exists('test file .txt'))->toBeTrue();
    });

    it('applies auto_rename duplicate policy on complete', function (): void {
        assert($this instanceof TestCase);
        Config::set('storage-manager.upload.duplicate_policy', 'auto_rename');
        app()->forgetInstance(ConfigRepository::class);
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

        $response = $this->postJson(route('storage-manager.api.fm.upload.complete'), $payload);
        $response->assertOk();

        $content = $response->streamedContent();
        expect($content)->toContain('"status":"completed"');
        expect($content)->toContain('"fileName":"test (1).txt"');
        expect(Storage::disk('local')->exists('test (1).txt'))->toBeTrue();
        expect(Storage::disk('local')->get('test (1).txt'))->toBe($fileContent);
    });

    it('applies error duplicate policy on complete', function (): void {
        assert($this instanceof TestCase);
        Config::set('storage-manager.upload.duplicate_policy', 'error');
        app()->forgetInstance(ConfigRepository::class);
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

        $response = $this->postJson(route('storage-manager.api.fm.upload.complete'), $payload);
        $response->assertOk();

        $content = $response->streamedContent();
        expect($content)->toContain('"status":"error"');
        expect($content)->toContain('File already exists: test.txt');
        expect(Storage::disk('local')->get('test.txt'))->toBe('existing content'); // Intact
    });

    it('applies overwrite duplicate policy on complete', function (): void {
        assert($this instanceof TestCase);
        Config::set('storage-manager.upload.duplicate_policy', 'overwrite');
        app()->forgetInstance(ConfigRepository::class);
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

        $response = $this->postJson(route('storage-manager.api.fm.upload.complete'), $payload);
        $response->assertOk();

        $content = $response->streamedContent();
        expect($content)->toContain('"status":"completed"');
        expect($content)->toContain('"fileName":"test.txt"');
        expect(Storage::disk('local')->exists('test.txt'))->toBeTrue();
        expect(Storage::disk('local')->get('test.txt'))->toBe($fileContent); // Overwritten
    });
});
