<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Controller;

use Carbon\CarbonImmutable;
use Illuminate\Routing\Controller;
use Kwaadpepper\LaravelStorageManager\Event\FileCreated;
use Kwaadpepper\LaravelStorageManager\Http\Dto\ErrorDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\Upload\UploadInitDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\Upload\UploadStatusDto;
use Kwaadpepper\LaravelStorageManager\Http\Dto\Upload\UploadSuccessDto;
use Kwaadpepper\LaravelStorageManager\Http\Request\Upload\UploadChunkRequest;
use Kwaadpepper\LaravelStorageManager\Http\Request\Upload\UploadCompleteRequest;
use Kwaadpepper\LaravelStorageManager\Http\Request\Upload\UploadInitRequest;
use Kwaadpepper\LaravelStorageManager\Http\Response\ApiResponse;
use Kwaadpepper\LaravelStorageManager\Lib\Event\EventDispatcher;
use Kwaadpepper\LaravelStorageManager\Lib\Upload\UploadSessionService;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Path\Path;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadId;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadMetadata;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadSessionStatus;

class UploadController extends Controller
{
    public function __construct(
        private readonly UploadSessionService $sessionService,
        private readonly EventDispatcher $eventDispatcher
    ) {
    }

    public function init(UploadInitRequest $request): ApiResponse
    {
        $uploadId = UploadId::generate();

        $this->sessionService->createSession($uploadId, new UploadMetadata(
            uploadId: $uploadId,
            fileName: $request->string('fileName')->value(),
            totalChunks: $request->integer('totalChunks'),
            totalSize: $request->integer('totalSize'),
            createdAt: CarbonImmutable::now()
        ));

        return ApiResponse::json(
            $this->presentInit($uploadId->value),
            ApiResponse::HTTP_OK
        );
    }

    public function chunk(UploadChunkRequest $request): ApiResponse
    {
        $this->sessionService->storeChunk(
            uploadId: $request->getUploadId(),
            chunkIndex: $request->integer('chunk_index'),
            file: $request->file('file')
        );

        return ApiResponse::json(
            $this->presentSuccess(),
            ApiResponse::HTTP_OK
        );
    }

    public function status(UploadId $uploadId): ApiResponse
    {
        $statusData = $this->sessionService->readStatus($uploadId);

        if ($statusData === null) {
            return ApiResponse::json(
                $this->presentStatus(UploadSessionStatus::waiting()),
                ApiResponse::HTTP_OK
            );
        }

        return ApiResponse::json(
            $this->presentStatus($statusData),
            ApiResponse::HTTP_OK
        );
    }

    public function complete(UploadCompleteRequest $request): ApiResponse
    {
        // NOTE: Disabling time limit and ignoring user abort to ensure the upload completes even if the client disconnects
        set_time_limit(0);
        ignore_user_abort(true);

        $uploadId        = $request->getUploadId();
        $fileName        = $request->string('fileName')->value();
        $destinationPath = $request->getPath();

        $finalDestination = Path::appendTo($destinationPath, $fileName);
        $selectedDiskName = $request->getDisk()->name;

        try {
            $this->sessionService->assembleAndTransfer(
                $uploadId,
                $finalDestination,
                $selectedDiskName
            );
        } catch (\RuntimeException $e) {
            return ApiResponse::json(
                $this->presentError($e->getMessage()),
                ApiResponse::HTTP_BAD_REQUEST
            );
        }

        $this->eventDispatcher->dispatch(
            FileCreated::class,
            ['path' => $finalDestination->value]
        );

        return ApiResponse::json(
            $this->presentSuccess(),
            ApiResponse::HTTP_OK
        );
    }

    private function presentStatus(UploadSessionStatus $status): UploadStatusDto
    {
        return new UploadStatusDto(
            $status->status,
            $status->progress
        );
    }

    private function presentInit(string $uploadId): UploadInitDto
    {
        return new UploadInitDto($uploadId);
    }

    private function presentSuccess(): UploadSuccessDto
    {
        return new UploadSuccessDto();
    }

    private function presentError(string $message): ErrorDto
    {
        return new ErrorDto($message);
    }
}
