<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Kwaadpepper\LaravelStorageManager\Exception\IllegalDomainStateException;

class UploadMetadata
{
    public function __construct(
        public readonly UploadId $uploadId,
        public readonly string $fileName,
        public readonly int $totalChunks,
        public readonly int $totalSize,
        public readonly CarbonInterface $createdAt
    ) {
        if (trim($this->fileName) === '') {
            throw new IllegalDomainStateException('fileName cannot be empty.');
        }
        if ($this->totalChunks <= 0) {
            throw new IllegalDomainStateException('totalChunks must be greater than 0.');
        }
        if ($this->totalSize <= 0) {
            throw new IllegalDomainStateException('totalSize must be greater than 0.');
        }
        if ($this->createdAt->lessThanOrEqualTo(new \DateTimeImmutable('@0'))) {
            throw new IllegalDomainStateException('createdAt must be a valid timestamp.');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['fileName'], $data['totalChunks'], $data['totalSize'], $data['created_at'])) {
            throw new \InvalidArgumentException('Missing required fields for UploadMetadata.');
        }

        $uploadIdValue = isset($data['uploadId']) && is_string($data['uploadId']) ? $data['uploadId'] : null;

        return new self(
            uploadId: $uploadIdValue ? new UploadId($uploadIdValue) : UploadId::generate(),
            fileName: Arr::string($data, 'fileName'),
            totalChunks: Arr::integer($data, 'totalChunks'),
            totalSize: Arr::integer($data, 'totalSize'),
            createdAt: CarbonImmutable::parse(Arr::string($data, 'created_at')),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'uploadId'    => $this->uploadId->value,
            'fileName'    => $this->fileName,
            'totalChunks' => $this->totalChunks,
            'totalSize'   => $this->totalSize,
            'created_at'  => $this->createdAt->toIso8601String(),
        ];
    }
}
