<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\Stream;

use Illuminate\Support\Arr;
use Kwaadpepper\LaravelStorageManager\Enum\UploadStatus;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadSessionStatus;
use php_user_filter;

class UploadProgressFilter extends php_user_filter
{
    public const STATUS_FILE_PARAM = 'status_file_path';

    public const TOTAL_SIZE_PARAM = 'total_size';

    public const ON_PROGRESS_PARAM = 'on_progress';

    private int $bytesRead = 0;

    private int $totalSize = 0;

    private string $statusFilePath = '';

    /** @var callable|null */
    private $onProgress = null;

    private int $lastUpdate = 0;

    public function onCreate(): bool
    {
        $params = Arr::wrap($this->params);

        if (! Arr::has($params, self::STATUS_FILE_PARAM) || ! Arr::has($params, self::TOTAL_SIZE_PARAM)) {
            return false;
        }

        $this->statusFilePath = trim(Arr::string($params, self::STATUS_FILE_PARAM));
        $this->totalSize      = Arr::integer($params, self::TOTAL_SIZE_PARAM);
        $onProgress           = Arr::get($params, self::ON_PROGRESS_PARAM);
        $this->onProgress     = is_callable($onProgress) ? $onProgress : null;

        return true;
    }

    public function filter($in, $out, &$consumed, $closing): int
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            $this->bytesRead += $bucket->datalen;
            $consumed        += $bucket->datalen;

            $now = time();
            // ? At least 1 second between updates to avoid excessive writes
            if ($now > $this->lastUpdate) {
                $progress = $this->getProgressPercentage();
                $this->updateStatusFile($progress);
                if (is_callable($this->onProgress)) {
                    ($this->onProgress)('transferring', $progress);
                }
                $this->lastUpdate = $now;
            }

            stream_bucket_append($out, $bucket);
        }

        return PSFS_PASS_ON;
    }

    private function getProgressPercentage(): int
    {
        return min(100, (int) round(($this->bytesRead / max($this->totalSize, 1)) * 100));
    }

    private function updateStatusFile(int $progress): void
    {
        if ($this->statusFilePath) {
            $status = new UploadSessionStatus(
                UploadStatus::TRANSFERRING,
                $progress
            );
            file_put_contents(
                $this->statusFilePath,
                json_encode($status->toArray(), JSON_THROW_ON_ERROR)
            );
        }
    }
}
