import { computed, ref, watch, type ComputedRef, type Ref } from 'vue';
import { useService } from '@ts/service-container';
import { ModalState, useFileManagerStore, useUiStore } from '@ts/stores';
import { handleFormSubmission } from '@ts/tools';
import type { Path } from '@ts/types';

export interface ZipViewModel {
  isOpen: ComputedRef<boolean>;
  itemCount: ComputedRef<number>;
  archiveName: Ref<string>;
  archiveNameError: Ref<string | null>;
  isSubmitting: Ref<boolean>;
  formErrorMessage: Ref<string | null>;
  submit: () => Promise<boolean>;
  close: () => void;
}

export function useZipViewModel(): ZipViewModel {
  const uiStore = useUiStore();
  const fileManagerStore = useFileManagerStore();
  const fileManagerService = useService('fileManagerService');
  const eventQueueService = useService('eventQueueService');

  const isOpen = computed(() => uiStore.zipModal === ModalState.Opened);
  const selectedPaths = computed(() => Object.keys(fileManagerStore.selectedNodes) as Path[]);
  const itemCount = computed(() => selectedPaths.value.length);

  const archiveName = ref('archive.zip');
  const archiveNameError = ref<string | null>(null);
  const isSubmitting = ref(false);
  const formErrorMessage = ref<string | null>(null);

  watch(isOpen, (opened) => {
    if (opened) {
      const firstEntry = Object.values(fileManagerStore.selectedNodes)[0];
      if (firstEntry && itemCount.value === 1) {
        const base = firstEntry.name.replace(/\.[^/.]+$/, '');
        archiveName.value = `${base}.zip`;
      } else {
        archiveName.value = 'archive.zip';
      }
      archiveNameError.value = null;
      formErrorMessage.value = null;
    }
  });

  watch(archiveName, () => {
    if (archiveNameError.value) archiveNameError.value = null;
    if (formErrorMessage.value) formErrorMessage.value = null;
  });

  async function submit(): Promise<boolean> {
    if (isSubmitting.value || itemCount.value === 0) return false;

    let name = archiveName.value.trim();
    if (!name) {
      archiveNameError.value = 'Archive name is required';
      return false;
    }

    if (!name.toLowerCase().endsWith('.zip')) {
      name += '.zip';
      archiveName.value = name;
    }

    const currentDir = fileManagerStore.currentPath;
    const destPath = (currentDir === '/' ? `/${name}` : `${currentDir}/${name}`) as Path;
    const paths = [...selectedPaths.value];

    return handleFormSubmission({
      execute: async () => {
        await eventQueueService.pushAndWait({
          id: `zip-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`,
          type: 'ZIP',
          sourcePath: currentDir,
          destinationPath: destPath,
          execute: async (): Promise<void> => {
            await fileManagerService.zip(paths, destPath);
          },
        });
      },
      isSubmitting,
      formErrorMessage,
      fieldErrors: { archiveName: archiveNameError },
      fallbackMessage: 'Failed to create zip archive',
      onSuccess: () => {
        fileManagerStore.selectNodes();
        close();
      },
    });
  }

  function close(): void {
    archiveNameError.value = null;
    formErrorMessage.value = null;
    isSubmitting.value = false;
    uiStore.zipModal = ModalState.Closed;
  }

  return {
    isOpen,
    itemCount,
    archiveName,
    archiveNameError,
    isSubmitting,
    formErrorMessage,
    submit,
    close,
  };
}
