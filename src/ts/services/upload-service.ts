import { uploadInitSchema, uploadSuccessSchema } from '@ts/schemas/file-manager';
import { ConfigStore } from '@ts/stores/config-store';
import { FileManagerStore } from '@ts/stores/file-manager-store';
import { PickerStore } from '@ts/stores/picker-store';
import { UploadItem, UploadStore } from '@ts/stores/upload-store';
import { uniqueId } from '@ts/tools';
import SparkMD5 from 'spark-md5';
import { ApiService } from './api-service';
import { DiskService } from './disk-service';
import { NavigationService } from './navigation-service';
import { ToastService } from './toast-service';

const MAX_CONCURRENT_UPLOADS = 2;

export class UploadService {
  private activeUploads = new Set<string>();
  private refreshTimeout: ReturnType<typeof setTimeout> | null = null;

  constructor(
    private readonly apiService: ApiService,
    private readonly navigationService: NavigationService,
    private readonly uploadStore: UploadStore,
    private readonly configStore: ConfigStore,
    private readonly fileManagerStore: FileManagerStore,
    private readonly diskService?: DiskService,
    private readonly pickerStore?: PickerStore,
    private readonly toastService?: ToastService,
  ) {
    window.addEventListener('beforeunload', (e) => {
      if (this.activeUploads.size > 0) {
        e.preventDefault();
        e.returnValue = '';
      }
    });
  }

  public enqueueUploads(
    files: File[],
    destinationPath: string,
    disk: string,
    duplicatePolicy?: 'overwrite' | 'auto_rename' | 'error'
  ): void {
    const allowed = this.configStore.allowedExtensions.map((ext) =>
      ext.toLowerCase().replace(/^\./, '').trim()
    );

    files.forEach((file) => {
      const ext = file.name.includes('.')
        ? (file.name.split('.').pop()?.toLowerCase().trim() ?? '')
        : '';

      if (allowed.length > 0 && (!ext || !allowed.includes(ext))) {
        this.toastService?.pushToast({
          message: `File "${file.name}" has an unsupported extension.`,
          type: 'error',
        });
        return;
      }

      if (
        this.pickerStore?.scopedType &&
        !this.pickerStore.isFileAllowed({ name: file.name, extension: ext })
      ) {
        this.toastService?.pushToast({
          message: `File "${file.name}" does not match the required type (${this.pickerStore.scopedType}).`,
          type: 'error',
        });
        return;
      }

      this.uploadStore.addUpload({
        id: uniqueId(),
        file,
        fileName: file.name,
        destinationPath,
        disk,
        totalSize: file.size,
        duplicatePolicy,
      });
    });

    this.processQueue();
  }

  public processQueue(): void {
    const pendingUploads = this.uploadStore.uploads.filter((u) => u.status === 'pending');

    while (this.activeUploads.size < MAX_CONCURRENT_UPLOADS && pendingUploads.length > 0) {
      const upload = pendingUploads.shift();
      if (upload) {
        this.startUpload(upload);
      }
    }
  }

  private async startUpload(item: UploadItem): Promise<void> {
    if (!item.file) return;

    this.activeUploads.add(item.id);
    this.uploadStore.setStatus(item.id, 'uploading');

    try {
      // 1. Init
      const chunkMinSize = this.configStore.chunkMinSize;
      const chunkMaxSize = this.configStore.chunkMaxSize;
      const adaptiveChunkSize = Math.min(
        chunkMaxSize,
        Math.max(chunkMinSize, Math.ceil(item.totalSize / 100))
      );

      const { upload_id } = await this.apiService.post(
        this.configStore.routes.fmUploadInit.toString(),
        {
          fileName: item.fileName,
          totalSize: item.totalSize,
          totalChunks: Math.ceil(item.totalSize / adaptiveChunkSize),
        },
        uploadInitSchema
      );

      // 2. Upload chunks
      const chunkSize = adaptiveChunkSize;
      const totalChunks = Math.ceil(item.totalSize / chunkSize) || 1; // At least 1 chunk for empty files
      let uploadedBytes = 0;

      const maxConcurrent = 4;
      let currentIndex = 0;
      let hasError = false;
      let errorMessage = '';

      if (!item.file) {
        throw new Error('File object is missing');
      }

      const file = item.file; // type narrowed

      const worker = async () => {
        while (currentIndex < totalChunks && !hasError) {
          const chunkIndex = currentIndex++;
          try {
            const start = chunkIndex * chunkSize;
            const end = Math.min(start + chunkSize, item.totalSize);
            const blob = file.slice(start, end);

            const chunkFile = new File([blob], item.fileName, { type: file.type });

            const arrayBuffer = await chunkFile.arrayBuffer();
            const spark = new SparkMD5.ArrayBuffer();
            spark.append(arrayBuffer);
            const chunk_checksum = spark.end();

            const formData = new FormData();
            formData.append('upload_id', upload_id);
            formData.append('chunk_index', String(chunkIndex));
            formData.append('file', chunkFile);
            formData.append('chunk_checksum', chunk_checksum);

            await this.apiService.postMultiPart(
              this.configStore.routes.fmUploadChunk.toString(),
              formData,
              uploadSuccessSchema
            );

            uploadedBytes += blob.size;
            this.uploadStore.updateProgress(item.id, uploadedBytes, item.totalSize);
          } catch (e) {
            hasError = true;
            errorMessage = e instanceof Error ? e.message : 'Chunk upload failed';
          }
        }
      };

      const workers = Array.from({ length: Math.min(maxConcurrent, totalChunks) }, worker);
      await Promise.all(workers);

      if (hasError) {
        throw new Error(errorMessage);
      }

      // 3. Complete (with SSE streaming)
      this.uploadStore.setStatus(item.id, 'assembling');

      const finalFileName = await this.streamComplete(upload_id, item, totalChunks);

      this.uploadStore.setStatus(item.id, 'success', undefined, finalFileName);

      // Trigger directory refresh with a debounce
      if (this.fileManagerStore.currentPath === item.destinationPath) {
        if (this.refreshTimeout) {
          clearTimeout(this.refreshTimeout);
        }
        this.refreshTimeout = setTimeout(() => {
          this.navigationService.refreshCurrentPath();
          void this.diskService?.fetchDiskStats(undefined, true);
        }, 500);
      } else {
        void this.diskService?.fetchDiskStats(undefined, true);
      }
    } catch (error) {
      const errorMessage = error instanceof Error ? error.message : 'Upload failed';
      this.uploadStore.setStatus(item.id, 'error', errorMessage);
    } finally {
      this.activeUploads.delete(item.id);
      this.processQueue();
    }
  }

  private async streamComplete(
    upload_id: string,
    item: UploadItem,
    totalChunks: number
  ): Promise<string | undefined> {
    const url = this.configStore.routes.fmUploadComplete.toString();
    const csrf = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';

    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'text/event-stream',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrf,
      },
      body: JSON.stringify({
        upload_id,
        fileName: item.fileName,
        totalChunks,
        path: item.destinationPath,
        disk: item.disk,
        ...(item.duplicatePolicy ? { duplicatePolicy: item.duplicatePolicy } : {}),
      }),
    });

    if (!response.ok || !response.body) {
      throw new Error(`Upload complete failed: ${response.status}`);
    }

    const reader = response.body.getReader();
    const decoder = new TextDecoder();
    let buffer = '';

    while (true) {
      const { value, done } = await reader.read();
      if (done) break;

      buffer += decoder.decode(value, { stream: true });
      const lines = buffer.split('\n');
      buffer = lines.pop() ?? ''; // keep incomplete fragment

      for (const line of lines) {
        if (!line.startsWith('data: ')) continue;
        const dataStr = line.slice(6);
        if (!dataStr.trim()) continue;

        try {
          const data = JSON.parse(dataStr) as {
            status: string;
            progress?: number;
            message?: string;
            fileName?: string;
          };

          if (data.status === 'error') {
            throw new Error(data.message ?? 'Assembly failed');
          }

          if (data.status === 'completed') {
            return data.fileName; // finished
          }

          if (
            (data.status === 'assembling' || data.status === 'transferring') &&
            data.progress !== undefined
          ) {
            const newProgress = 50 + data.progress * 0.5;
            this.uploadStore.updateAssemblyProgress(item.id, newProgress);
          }
        } catch {
          // ignore JSON parse errors for incomplete chunks
        }
      }
    }
  }
}
