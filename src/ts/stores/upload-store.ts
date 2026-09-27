import { defineStore } from 'pinia';
import { ref, watch } from 'vue';

export type UploadStatus = 'pending' | 'uploading' | 'assembling' | 'success' | 'error';

export interface UploadItem {
  id: string;
  file?: File;
  fileName: string;
  finalFileName?: string;
  destinationPath: string;
  disk: string;
  totalSize: number;
  uploadedBytes: number;
  progress: number; // 0 to 100
  status: UploadStatus;
  duplicatePolicy?: 'overwrite' | 'auto_rename' | 'error';
  error?: string;
}

const STORAGE_KEY = 'sm-upload-store';

function loadFromLocalStorage(): UploadItem[] {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (!raw) return [];
    const parsed = JSON.parse(raw);
    return Array.isArray(parsed?.uploads) ? parsed.uploads : [];
  } catch {
    return [];
  }
}

function saveToLocalStorage(items: UploadItem[]): void {
  try {
    const serialized = items.map((u) => {
      const { file: _file, ...rest } = u;
      if (['pending', 'uploading', 'assembling'].includes(rest.status)) {
        return { ...rest, status: 'error' as const, error: 'Upload interrupted by page reload' };
      }
      return rest;
    });
    localStorage.setItem(STORAGE_KEY, JSON.stringify({ uploads: serialized }));
  } catch {
    // ignore storage write errors
  }
}

export const useUploadStore = defineStore('upload', () => {
  const uploads = ref<UploadItem[]>(loadFromLocalStorage());

  watch(
    uploads,
    (val) => {
      saveToLocalStorage(val);
    },
    { deep: true }
  );

  function addUpload(upload: Omit<UploadItem, 'uploadedBytes' | 'progress' | 'status'>): void {
    uploads.value.push({
      ...upload,
      uploadedBytes: 0,
      progress: 0,
      status: 'pending',
    });
  }

  function updateProgress(
    id: string,
    uploadedBytes: number,
    totalSize: number,
    status?: UploadStatus
  ): void {
    uploads.value = uploads.value.map((u) => {
      if (u.id === id) {
        const chunkProgress = Math.round((uploadedBytes / totalSize) * 50);
        return {
          ...u,
          uploadedBytes,
          progress: status === 'assembling' ? u.progress : chunkProgress,
          ...(status && { status }),
        };
      }
      return u;
    });
  }

  function setStatus(
    id: string,
    status: UploadStatus,
    error?: string,
    finalFileName?: string
  ): void {
    uploads.value = uploads.value.map((u) => {
      if (u.id === id) {
        const progress = status === 'success' ? 100 : u.progress;
        return {
          ...u,
          status,
          error,
          progress,
          ...(finalFileName && { finalFileName }),
        };
      }
      return u;
    });
  }

  function updateAssemblyProgress(id: string, progress: number): void {
    const target = uploads.value.find((u) => u.id === id);
    if (target) {
      target.progress = Math.max(target.progress, progress);
    }
  }

  function removeUpload(id: string): void {
    uploads.value = uploads.value.filter((u) => u.id !== id);
  }

  function clearCompleted(): void {
    uploads.value = uploads.value.filter(
      (u) =>
        u.status === 'pending' ||
        u.status === 'uploading' ||
        u.status === 'assembling'
    );
  }

  return {
    uploads,
    addUpload,
    updateProgress,
    updateAssemblyProgress,
    setStatus,
    removeUpload,
    clearCompleted,
  };
});

export type UploadStore = ReturnType<typeof useUploadStore>;
