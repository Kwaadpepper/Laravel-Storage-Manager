import { computed, ref, watch, type ComputedRef } from 'vue';
import { useService } from '@ts/service-container';
import { ModalState, useUiStore } from '@ts/stores';
import {
  SUPPORTED_AUDIO_EXTENSIONS,
  SUPPORTED_IMAGE_EXTENSIONS,
  SUPPORTED_PDF_EXTENSIONS,
  SUPPORTED_VIDEO_EXTENSIONS,
} from '../../../utils/supported-extensions';

type FetchState =
  | { status: 'pending' }
  | { status: 'success'; blob: Blob | null; streamUrl: string; downloadUrl: string }
  | { status: 'error'; message: string };

export interface ViewFileViewModel {
  isOpen: ComputedRef<boolean>;
  close: () => void;
  targetFilePath: ComputedRef<string | null>;
  blob: ComputedRef<Blob | null>;
  streamUrl: ComputedRef<string | null>;
  downloadUrl: ComputedRef<string | null>;
  loading: ComputedRef<boolean>;
  error: ComputedRef<string | null>;
}

export function useViewFileViewModel(): ViewFileViewModel {
  const uiStore = useUiStore();
  const downloadService = useService('downloadService');

  const fetchState = ref<FetchState>({ status: 'pending' });

  const isOpen = computed(() => uiStore.viewFileModal === ModalState.Opened);
  const targetFilePath = computed(() => uiStore.targetFilePath);

  function getExtension(path: string): string | null {
    const filename = path.split('/').pop() ?? '';
    return filename.includes('.') ? filename.split('.').pop()?.toLowerCase() ?? null : null;
  }

  function isStreamable(ext: string | null): boolean {
    if (!ext) return false;
    return (
      SUPPORTED_VIDEO_EXTENSIONS.includes(ext) ||
      SUPPORTED_AUDIO_EXTENSIONS.includes(ext) ||
      SUPPORTED_IMAGE_EXTENSIONS.includes(ext) ||
      SUPPORTED_PDF_EXTENSIONS.includes(ext)
    );
  }

  watch(
    [() => isOpen.value, () => targetFilePath.value],
    ([open, path], _, onCleanup) => {
      if (!open || !path) return;

      const ext = getExtension(path);
      const streamUrl = downloadService.getStreamUrl(path);
      const downloadUrl = downloadService.getDownloadUrl(path);

      // If the media can be streamed directly via URL (video, audio, image, pdf),
      // we do not download the entire file into a JS Blob, enabling instant playback & HTTP Range seeking.
      if (isStreamable(ext)) {
        fetchState.value = {
          status: 'success',
          blob: null,
          streamUrl,
          downloadUrl,
        };
        return;
      }

      // If it requires text decoding or is unknown, download as Blob
      let cancelled = false;
      fetchState.value = { status: 'pending' };

      downloadService
        .downloadFile(path)
        .then((blob) => {
          if (!cancelled) {
            fetchState.value = {
              status: 'success',
              blob,
              streamUrl,
              downloadUrl,
            };
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
    uiStore.viewFileModal = ModalState.Closed;
  }

  const blob = computed(() => (fetchState.value.status === 'success' ? fetchState.value.blob : null));
  const streamUrl = computed(() => (fetchState.value.status === 'success' ? fetchState.value.streamUrl : null));
  const downloadUrl = computed(() => (fetchState.value.status === 'success' ? fetchState.value.downloadUrl : null));
  const loading = computed(() => isOpen.value && fetchState.value.status === 'pending');
  const error = computed(() => (fetchState.value.status === 'error' ? fetchState.value.message : null));

  return {
    isOpen,
    close,
    targetFilePath,
    blob,
    streamUrl,
    downloadUrl,
    loading,
    error,
  };
}
