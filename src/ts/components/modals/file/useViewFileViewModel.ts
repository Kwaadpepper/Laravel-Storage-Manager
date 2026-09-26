import { computed, ref, watch, type ComputedRef } from 'vue';
import { useService } from '@ts/service-container';
import { ModalState, useUiStore } from '@ts/stores';

type FetchState =
  | { status: 'pending' }
  | { status: 'success'; blob: Blob }
  | { status: 'error'; message: string };

export interface ViewFileViewModel {
  isOpen: ComputedRef<boolean>;
  close: () => void;
  targetFilePath: ComputedRef<string | null>;
  blob: ComputedRef<Blob | null>;
  loading: ComputedRef<boolean>;
  error: ComputedRef<string | null>;
}

export function useViewFileViewModel(): ViewFileViewModel {
  const uiStore = useUiStore();
  const downloadService = useService('downloadService');

  const fetchState = ref<FetchState>({ status: 'pending' });

  const isOpen = computed(() => uiStore.viewFileModal === ModalState.Opened);
  const targetFilePath = computed(() => uiStore.targetFilePath);

  watch(
    [() => isOpen.value, () => targetFilePath.value],
    ([open, path], _, onCleanup) => {
      if (!open || !path) return;

      let cancelled = false;
      fetchState.value = { status: 'pending' };

      downloadService
        .downloadFile(path)
        .then((blob) => {
          if (!cancelled) {
            fetchState.value = { status: 'success', blob };
          }
        })
        .catch(() => {
          if (!cancelled) {
            fetchState.value = { status: 'error', message: 'Failed to load file.' };
          }
        });

      onCleanup(() => {
        cancelled = true;
      });
    },
    { immediate: true }
  );

  function close(): void {
    fetchState.value = { status: 'pending' };
    uiStore.setViewFileModal(ModalState.Closed);
  }

  const blob = computed(() => (fetchState.value.status === 'success' ? fetchState.value.blob : null));
  const loading = computed(() => isOpen.value && fetchState.value.status === 'pending');
  const error = computed(() => (fetchState.value.status === 'error' ? fetchState.value.message : null));

  return {
    isOpen,
    close,
    targetFilePath,
    blob,
    loading,
    error,
  };
}
