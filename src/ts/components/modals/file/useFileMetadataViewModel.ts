import { computed, ref, watch, type ComputedRef, type Ref } from 'vue';
import { useFileManager } from '@ts/composables';
import { ModalState, useUiStore } from '@ts/stores';
import { FileMetadata } from '@ts/types';

export interface FileMetadataViewModel {
  isOpen: ComputedRef<boolean>;
  targetFilePath: ComputedRef<string | null>;
  metadata: Ref<FileMetadata | null>;
  loading: Ref<boolean>;
  error: Ref<string | null>;
  close: () => void;
  refresh: () => Promise<void>;
}

export function useFileMetadataViewModel(): FileMetadataViewModel {
  const uiStore = useUiStore();
  const fileManager = useFileManager();

  const isOpen = computed(() => uiStore.fileMetadataModal === ModalState.Opened);
  const targetFilePath = computed(() => uiStore.targetFilePath);

  const metadata = ref<FileMetadata | null>(null);
  const loading = ref<boolean>(false);
  const error = ref<string | null>(null);

  async function loadMetadata(path: string): Promise<void> {
    loading.value = true;
    error.value = null;

    try {
      metadata.value = await fileManager.getFileProperties(path as import('@ts/types').Path);
    } catch (err: unknown) {
      metadata.value = null;
      error.value = err instanceof Error ? err.message : 'Failed to load file metadata.';
    } finally {
      loading.value = false;
    }
  }

  watch(
    [() => isOpen.value, () => targetFilePath.value],
    ([open, path]) => {
      if (open && path) {
        void loadMetadata(path);
      } else if (!open) {
        metadata.value = null;
        error.value = null;
        loading.value = false;
      }
    },
    { immediate: true }
  );

  function close(): void {
    uiStore.fileMetadataModal = ModalState.Closed;
  }

  async function refresh(): Promise<void> {
    if (targetFilePath.value) {
      await loadMetadata(targetFilePath.value);
    }
  }

  return {
    isOpen,
    targetFilePath,
    metadata,
    loading,
    error,
    close,
    refresh,
  };
}
