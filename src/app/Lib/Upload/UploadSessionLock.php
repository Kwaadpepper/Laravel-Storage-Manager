<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\Upload;

use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Enum\UploadLockDriver;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadId;
use Psr\Log\LoggerInterface;

class UploadSessionLock
{
    public function __construct(
        private readonly UploadPathResolver $pathResolver,
        private readonly StorageManagerConfig $config,
        private readonly CacheRepository $cache,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function acquire(UploadId $uploadId, callable $callback, int $seconds = 10, int $waitSeconds = 3): mixed
    {
        $driver = $this->config->upload->lockDriver;

        if ($driver === UploadLockDriver::FLOCK) {
            return $this->withFileLock($uploadId, $callback, $waitSeconds);
        }

        try {
            $store = $this->cache->getStore();
            if (! $store instanceof LockProvider) {
                throw new \BadMethodCallException('This cache store does not support atomic locks.');
            }

            return $store->lock('sm:upload:status:' . $uploadId->value, $seconds)
                ->block($waitSeconds, $callback);
        } catch (\Throwable $e) {
            if ($driver === UploadLockDriver::CACHE) {
                throw $e;
            }

            $this->logger->debug('Cache lock failed or unsupported, falling back to flock', [
                'uploadId' => $uploadId->value,
                'error'    => $e->getMessage(),
            ]);

            return $this->withFileLock($uploadId, $callback, $waitSeconds);
        }
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    private function withFileLock(UploadId $uploadId, callable $callback, int $waitSeconds): mixed
    {
        $uploadDir = $this->pathResolver->getUploadDir($uploadId);
        $disk      = $this->pathResolver->getDisk();

        if (! $disk->exists($uploadDir)) {
            return $callback();
        }

        $lockFile = $this->pathResolver->getAbsolutePath(
            $this->pathResolver->getLockPath($uploadId)
        );

        $handle = false;
        try {
            $handle = fopen($lockFile, 'c+');
        } catch (\Throwable) {
            $handle = false;
        }

        if ($handle === false) {
            $this->logger->warning('Failed to open upload lock file, executing without file lock', [
                'uploadId' => $uploadId->value,
                'path'     => $lockFile,
            ]);

            return $callback();
        }

        $startTime = microtime(true);
        $acquired  = false;

        while ((microtime(true) - $startTime) < $waitSeconds) {
            if (flock($handle, LOCK_EX | LOCK_NB)) {
                $acquired = true;
                break;
            }
            usleep(10000);
        }

        if (! $acquired) {
            $this->logger->warning('File lock acquisition timed out, acquiring with blocking lock', [
                'uploadId' => $uploadId->value,
            ]);
            flock($handle, LOCK_EX);
        }

        try {
            return $callback();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
