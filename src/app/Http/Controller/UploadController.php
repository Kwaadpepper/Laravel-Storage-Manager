<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Http\Controller;

use Carbon\CarbonImmutable;
use Illuminate\Routing\Controller;
use Kwaadpepper\LaravelStorageManager\Event\FileCreated;
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
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function complete(UploadCompleteRequest $request): StreamedResponse
    {
        // NOTE: Disabling time limit and ignoring user abort to ensure the upload completes even if the client disconnects
        set_time_limit(0);
        ignore_user_abort(true);

        $uploadId        = $request->getUploadId();
        $fileName        = $request->string('fileName')->value();
        $destinationPath = $request->getPath();

        $finalDestination = Path::appendTo($destinationPath, $fileName);
        $selectedDiskName = $request->getDisk()->name;

        return response()->stream(function () use ($uploadId, $finalDestination, $selectedDiskName) {
            try {
                $this->sessionService->assembleAndTransfer(
                    $uploadId,
                    $finalDestination,
                    $selectedDiskName,
                    onProgress: function (string $status, int $progress): void {
                        echo 'data: ' . json_encode(compact('status', 'progress')) . "\n\n";
                        if (ob_get_level() > 0) {
                            ob_flush();
                        }
                        flush();
                    }
                );

                $this->eventDispatcher->dispatch(
                    FileCreated::class,
                    ['path' => $finalDestination->value]
                );

                echo 'data: ' . json_encode(['status' => 'completed', 'progress' => 100]) . "\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            } catch (\RuntimeException $e) {
                echo 'data: ' . json_encode(['status' => 'error', 'message' => $e->getMessage()]) . "\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
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
}
