<?php

declare(strict_types=1);

namespace Tests\Unit\Lib\Upload;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Storage;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Lib\Upload\UploadPathResolver;
use Kwaadpepper\LaravelStorageManager\Lib\Upload\UploadSessionLock;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadId;
use Mockery;
use Psr\Log\LoggerInterface;

describe('UploadSessionLock', function (): void {
    beforeEach(function (): void {
        Storage::fake('temp_disk');
        $this->logger       = Mockery::mock(LoggerInterface::class);
        $this->pathResolver = resolve(UploadPathResolver::class);
        $this->cache        = resolve(CacheRepository::class);
    });

    it('acquires lock via Cache lock when using CACHE driver', function (): void {
        // GIVEN
        $config = StorageManagerConfig::fromArray([
            'upload' => [
                'lock_driver' => 'cache',
            ],
        ]);
        $lockService = new UploadSessionLock($this->pathResolver, $config, $this->cache, $this->logger);
        $uploadId    = UploadId::generate();
        $executed    = false;

        // WHEN
        $result = $lockService->acquire($uploadId, function () use (&$executed): string {
            $executed = true;

            return 'success';
        });

        // THEN
        expect($executed)->toBeTrue();
        expect($result)->toBe('success');
    });

    it('acquires lock via file lock when using FLOCK driver', function (): void {
        // GIVEN
        $config = StorageManagerConfig::fromArray([
            'upload' => [
                'lock_driver' => 'flock',
            ],
        ]);
        $this->logger->shouldReceive('warning')->never();
        $lockService = new UploadSessionLock($this->pathResolver, $config, $this->cache, $this->logger);
        $uploadId    = UploadId::generate();
        $disk        = $this->pathResolver->getDisk();
        $disk->makeDirectory($this->pathResolver->getUploadDir($uploadId));
        $executed = false;

        // WHEN
        $result = $lockService->acquire($uploadId, function () use (&$executed): int {
            $executed = true;

            return 42;
        });

        // THEN
        expect($executed)->toBeTrue();
        expect($result)->toBe(42);
        expect($disk->exists($this->pathResolver->getLockPath($uploadId)))->toBeTrue();
    });

    it('falls back to file lock in AUTO mode when Cache lock throws', function (): void {
        // GIVEN
        $config = StorageManagerConfig::fromArray([
            'upload' => [
                'lock_driver' => 'auto',
            ],
        ]);
        $this->logger->shouldReceive('debug')->once();
        $cache = Mockery::mock(CacheRepository::class);
        $cache->shouldReceive('getStore')->andThrow(new \BadMethodCallException('This cache store does not support atomic locks.'));

        $lockService = new UploadSessionLock($this->pathResolver, $config, $cache, $this->logger);
        $uploadId    = UploadId::generate();
        $disk        = $this->pathResolver->getDisk();
        $disk->makeDirectory($this->pathResolver->getUploadDir($uploadId));

        $executed = false;

        // WHEN
        $result = $lockService->acquire($uploadId, function () use (&$executed): string {
            $executed = true;

            return 'fallback_done';
        });

        // THEN
        expect($executed)->toBeTrue();
        expect($result)->toBe('fallback_done');
    });

    it('rethrows exception when Cache lock fails and driver is CACHE', function (): void {
        // GIVEN
        $config = StorageManagerConfig::fromArray([
            'upload' => [
                'lock_driver' => 'cache',
            ],
        ]);
        $cache = Mockery::mock(CacheRepository::class);
        $cache->shouldReceive('getStore')->andThrow(new \RuntimeException('Cache service connection failed'));

        $lockService = new UploadSessionLock($this->pathResolver, $config, $cache, $this->logger);
        $uploadId    = UploadId::generate();

        // WHEN
        $action = fn (): mixed => $lockService->acquire($uploadId, fn (): bool => true);

        // THEN
        expect($action)->toThrow(\RuntimeException::class, 'Cache service connection failed');
    });

    it('executes callback directly when upload directory does not exist for file lock', function (): void {
        // GIVEN
        $config = StorageManagerConfig::fromArray([
            'upload' => [
                'lock_driver' => 'flock',
            ],
        ]);
        $lockService = new UploadSessionLock($this->pathResolver, $config, $this->cache, $this->logger);
        $uploadId    = UploadId::generate();
        $executed    = false;

        // WHEN
        $result = $lockService->acquire($uploadId, function () use (&$executed): string {
            $executed = true;

            return 'no_dir';
        });

        // THEN
        expect($executed)->toBeTrue();
        expect($result)->toBe('no_dir');
    });
})->covers(UploadSessionLock::class);
