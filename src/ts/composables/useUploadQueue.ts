import { getApiClient } from '@ts/clients';
import { uploadInitSchema, uploadSuccessSchema } from '@ts/schemas/file-manager';
import {
  useConfigStore,
  useDiskStore,
  useFileManagerStore,
  usePickerStore,
  useToastStore,
  useUploadStore,
  type UploadItem,
} from '@ts/stores';
import { uniqueId } from '@ts/tools';
import SparkMD5 from 'spark-md5';
import { useNavigation } from './useNavigation';

const MAX_CONCURRENT_UPLOADS = 2;

// Module-level state for active uploads across invocations
const activeUploads = new Set<string>();
let refreshTimeout: ReturnType<typeof setTimeout> | null = null;
let unloadListenerRegistered = false;

function ensureUnloadListener(): void {
  if (unloadListenerRegistered || typeof window === 'undefined') return;
  unloadListenerRegistered = true;
  window.addEventListener('beforeunload', (e) => {
    if (activeUploads.size > 0) {
      e.preventDefault();
      e.returnValue = '';
    }
  });
}

export function useUploadQueue() {
  const uploadStore = useUploadStore();
  const configStore = useConfigStore();
  const fileManagerStore = useFileManagerStore();
  const diskStore = useDiskStore();
  const pickerStore = usePickerStore();
  const toastStore = useToastStore();
  const navigation = useNavigation();
  const apiClient = getApiClient();

  ensureUnloadListener();

  function enqueueUploads(
    files: File[],
    destinationPath: string,
    disk: string,
    duplicatePolicy?: 'overwrite' | 'auto_rename' | 'error'
  ): void {
    const allowed = configStore.allowedExtensions.map((ext) =>
      ext.toLowerCase().replace(/^\./, '').trim()
    );

    files.forEach((file) => {
      const ext = file.name.includes('.')
        ? (file.name.split('.').pop()?.toLowerCase().trim() ?? '')
        : '';

      if (allowed.length > 0 && (!ext || !allowed.includes(ext))) {
        toastStore.pushToast({
          message: `File "${file.name}" has an unsupported extension.`,
          type: 'error',
        });
        return;
      }

      if (
        pickerStore.scopedType &&
        !pickerStore.isFileAllowed({ name: file.name, extension: ext })
      ) {
        toastStore.pushToast({
          message: `File "${file.name}" does not match the required type (${pickerStore.scopedType}).`,
          type: 'error',
        });
        return;
      }

      uploadStore.addUpload({
        id: uniqueId(),
        file,
        fileName: file.name,
        destinationPath,
        disk,
        totalSize: file.size,
        duplicatePolicy,
      });
    });

    processQueue();
  }

  function processQueue(): void {
    const pendingUploads = uploadStore.uploads.filter((u) => u.status === 'pending');

    while (activeUploads.size < MAX_CONCURRENT_UPLOADS && pendingUploads.length > 0) {
      const upload = pendingUploads.shift();
      if (upload) {
        void startUpload(upload);
      }
    }
  }

  async function startUpload(item: UploadItem): Promise<void> {
    if (!item.file) return;

    activeUploads.add(item.id);
    uploadStore.setStatus(item.id, 'uploading');

    try {
      // 1. Init
      const chunkMinSize = configStore.chunkMinSize;
      const chunkMaxSize = configStore.chunkMaxSize;
      const adaptiveChunkSize = Math.min(
        chunkMaxSize,
        Math.max(chunkMinSize, Math.ceil(item.totalSize / 100))
      );

      const { upload_id } = await apiClient.post(
        configStore.routes.fmUploadInit.toString(),
        {
          fileName: item.fileName,
          totalSize: item.totalSize,
          totalChunks: Math.ceil(item.totalSize / adaptiveChunkSize),
        },
        uploadInitSchema
      );

      // 2. Upload chunks
      const chunkSize = adaptiveChunkSize;
      const totalChunks = Math.ceil(item.totalSize / chunkSize) || 1;
      let uploadedBytes = 0;

      const maxConcurrent = 4;
      let currentIndex = 0;
      let hasError = false;
      let errorMessage = '';

      const file = item.file;

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

            await apiClient.postMultiPart(
              configStore.routes.fmUploadChunk.toString(),
              formData,
              uploadSuccessSchema
            );

            uploadedBytes += blob.size;
            uploadStore.updateProgress(item.id, uploadedBytes, item.totalSize);
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
      uploadStore.setStatus(item.id, 'assembling');

      const finalFileName = await streamComplete(upload_id, item, totalChunks);

      uploadStore.setStatus(item.id, 'success', undefined, finalFileName);

      // Trigger directory refresh with a debounce
      if (fileManagerStore.currentPath === item.destinationPath) {
        if (refreshTimeout) {
          clearTimeout(refreshTimeout);
        }
        refreshTimeout = setTimeout(() => {
          navigation.refreshCurrentPath();
          void diskStore.fetchDiskStats(undefined, true);
        }, 500);
      } else {
        void diskStore.fetchDiskStats(undefined, true);
      }
    } catch (error) {
      const errorMessage = error instanceof Error ? error.message : 'Upload failed';
      uploadStore.setStatus(item.id, 'error', errorMessage);
    } finally {
      activeUploads.delete(item.id);
      processQueue();
    }
  }

  async function streamComplete(
    upload_id: string,
    item: UploadItem,
    totalChunks: number
  ): Promise<string | undefined> {
    const url = configStore.routes.fmUploadComplete.toString();
    const csrf =
      document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';

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
      buffer = lines.pop() ?? '';

      for (const line of lines) {
        if (!line.startsWith('data: ')) continue;
        const dataStr = line.slice(6);
        if (!dataStr.trim()) continue;

        let data: {
          status: string;
          progress?: number;
          message?: string;
          fileName?: string;
        };
        try {
          data = JSON.parse(dataStr);
        } catch {
          // ignore JSON parse errors for incomplete chunks
          continue;
        }

        if (data.status === 'error') {
          throw new Error(data.message ?? 'Assembly failed');
        }

        if (data.status === 'completed') {
          return data.fileName;
        }

        if (
          (data.status === 'assembling' || data.status === 'transferring') &&
          data.progress !== undefined
        ) {
          const newProgress = 50 + data.progress * 0.5;
          uploadStore.updateAssemblyProgress(item.id, newProgress);
        }
      }
    }
  }

  return {
    enqueueUploads,
    processQueue,
  };
}

export class UploadService {
  private readonly uploadQueue: ReturnType<typeof useUploadQueue>;

  constructor() {
    this.uploadQueue = useUploadQueue();
  }

  public enqueueUploads(
    files: File[],
    destinationPath: string,
    disk: string,
    duplicatePolicy?: 'overwrite' | 'auto_rename' | 'error'
  ): void {
    this.uploadQueue.enqueueUploads(files, destinationPath, disk, duplicatePolicy);
  }

  public processQueue(): void {
    this.uploadQueue.processQueue();
  }
}
