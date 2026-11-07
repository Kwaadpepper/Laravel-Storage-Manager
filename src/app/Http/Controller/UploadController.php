<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Controller;

use Carbon\CarbonImmutable;
use Illuminate\Routing\Controller;
use Kwaadpepper\LaravelStorageManager\Action\Upload\CompleteUploadAction;
use Kwaadpepper\LaravelStorageManager\Action\Upload\CompleteUploadInput;
use Kwaadpepper\LaravelStorageManager\Action\Upload\InitUploadAction;
use Kwaadpepper\LaravelStorageManager\Action\Upload\InitUploadInput;
use Kwaadpepper\LaravelStorageManager\Action\Upload\UploadChunkAction;
use Kwaadpepper\LaravelStorageManager\Action\Upload\UploadChunkInput;
use Kwaadpepper\LaravelStorageManager\Contract\Executor;
use Kwaadpepper\LaravelStorageManager\Enum\DuplicatePolicy;
use Kwaadpepper\LaravelStorageManager\Http\Dto\Upload\UploadInitDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\Upload\UploadStatusDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\Upload\UploadSuccessDto;
use Kwaadpepper\LaravelStorageManager\Http\Request\Upload\UploadChunkRequest;
use Kwaadpepper\LaravelStorageManager\Http\Request\Upload\UploadCompleteRequest;
use Kwaadpepper\LaravelStorageManager\Http\Request\Upload\UploadInitRequest;
use Kwaadpepper\LaravelStorageManager\Http\Response\ApiResponse;
use Kwaadpepper\LaravelStorageManager\Http\Response\StreamResponseFactory;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadId;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadMetadata;
use Kwaadpepper\LaravelStorageManager\Query\Upload\GetUploadStatusInput;
use Kwaadpepper\LaravelStorageManager\Query\Upload\GetUploadStatusQuery;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UploadController extends Controller
{
    public function __construct(
        private readonly Executor $executor,
        private readonly InitUploadAction $initAction,
        private readonly UploadChunkAction $chunkAction,
        private readonly GetUploadStatusQuery $statusQuery,
        private readonly CompleteUploadAction $completeAction,
    ) {
    }

    public function init(UploadInitRequest $request): ApiResponse
    {
        $uploadId = UploadId::generate();

        $this->executor->execute($this->initAction, new InitUploadInput(
            $uploadId,
            new UploadMetadata(
                uploadId: $uploadId,
                fileName: $request->string('fileName')->value(),
                totalChunks: $request->integer('totalChunks'),
                totalSize: $request->integer('totalSize'),
                createdAt: CarbonImmutable::now()
            )
        ));

        return ApiResponse::json(
            new UploadInitDto($uploadId->value),
            ApiResponse::HTTP_OK
        );
    }

    public function chunk(UploadChunkRequest $request): ApiResponse
    {
        $this->executor->execute($this->chunkAction, new UploadChunkInput(
            uploadId: $request->getUploadId(),
            chunkIndex: $request->integer('chunk_index'),
            file: $request->getUploadedFile()
        ));

        return ApiResponse::json(
            new UploadSuccessDto(),
            ApiResponse::HTTP_OK
        );
    }

    public function status(UploadId $uploadId): ApiResponse
    {
        $statusData = $this->executor->execute(
            $this->statusQuery,
            new GetUploadStatusInput($uploadId)
        );

        return ApiResponse::json(
            new UploadStatusDto($statusData->status, $statusData->progress),
            ApiResponse::HTTP_OK
        );
    }

    public function complete(UploadCompleteRequest $request): StreamedResponse
    {
        $uploadId        = $request->getUploadId();
        $fileName        = $request->string('fileName')->value();
        $destinationPath = $request->getPath();
        $disk            = $request->getDisk();
        $requestedPolicy = $request->filled('duplicatePolicy')
            ? DuplicatePolicy::tryFrom($request->string('duplicatePolicy')->value())
            : null;

        return StreamResponseFactory::sse(function (callable $emit) use ($uploadId, $disk, $destinationPath, $fileName, $requestedPolicy): void {
            try {
                $finalFileName = $this->executor->execute($this->completeAction, new CompleteUploadInput(
                    uploadId: $uploadId,
                    disk: $disk,
                    destinationDir: $destinationPath,
                    fileName: $fileName,
                    requestedPolicy: $requestedPolicy,
                    onProgress: function (string $status, int $progress) use ($emit): void {
                        $emit(compact('status', 'progress'));
                    }
                ));

                $emit(['status' => 'completed', 'progress' => 100, 'fileName' => $finalFileName]);
            } catch (\Throwable $e) {
                $emit(['status' => 'error', 'message' => $e->getMessage()]);
            }
        });
    }
}
