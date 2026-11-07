import { computed, ref, watch, type ComputedRef, type Ref } from 'vue';
import { useFileManager, useEventQueue } from '@ts/composables';
import { ModalState, useFileManagerStore, useUiStore } from '@ts/stores';
import { handleFormSubmission } from '@ts/tools';
import type { Path } from '@ts/types';

export interface UnzipViewModel {
  isOpen: ComputedRef<boolean>;
  archivePath: ComputedRef<Path | null>;
  archiveName: ComputedRef<string>;
  extractMode: Ref<'current' | 'subfolder'>;
  subfolderName: Ref<string>;
  isSubmitting: Ref<boolean>;
  formErrorMessage: Ref<string | null>;
  submit: () => Promise<boolean>;
  close: () => void;
}

export function useUnzipViewModel(): UnzipViewModel {
  const uiStore = useUiStore();
  const fileManagerStore = useFileManagerStore();
  const fileManager = useFileManager();
  const { pushAndWait } = useEventQueue();

  const isOpen = computed(() => uiStore.unzipModal === ModalState.Opened);

  const archivePath = computed<Path | null>(() => {
    if (uiStore.targetArchiveFilePath) {
      return uiStore.targetArchiveFilePath;
    }
    const selected = Object.keys(fileManagerStore.selectedNodes) as Path[];
    if (selected.length === 1 && selected[0]?.toLowerCase().endsWith('.zip')) {
      return selected[0];
    }
    return null;
  });

  const archiveName = computed(() => {
    if (!archivePath.value) return '';
    return archivePath.value.split('/').pop() ?? '';
  });

  const extractMode = ref<'current' | 'subfolder'>('subfolder');
  const subfolderName = ref('');
  const isSubmitting = ref(false);
  const formErrorMessage = ref<string | null>(null);

  watch(isOpen, (opened) => {
    if (opened && archiveName.value) {
      subfolderName.value = archiveName.value.replace(/\.zip$/i, '');
      extractMode.value = 'subfolder';
      formErrorMessage.value = null;
    }
  });

  async function submit(): Promise<boolean> {
    if (isSubmitting.value || !archivePath.value) return false;

    const currentDir = fileManagerStore.currentPath;
    let destinationDir: Path;

    if (extractMode.value === 'subfolder') {
      const cleanSub = subfolderName.value.trim();
      if (!cleanSub) {
        formErrorMessage.value = 'Subfolder name cannot be empty';
        return false;
      }
      destinationDir = (currentDir === '/' ? `/${cleanSub}` : `${currentDir}/${cleanSub}`) as Path;
    } else {
      destinationDir = currentDir;
    }

    const source = archivePath.value;

    return handleFormSubmission({
      execute: async () => {
        await pushAndWait({
          id: `unzip-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`,
          type: 'UNZIP',
          sourcePath: source,
          destinationPath: destinationDir,
          execute: async (): Promise<void> => {
            await fileManager.unzip(source, destinationDir);
          },
        });
      },
      isSubmitting,
      formErrorMessage,
      fieldErrors: {},
      fallbackMessage: 'Failed to extract zip archive',
      onSuccess: () => {
        fileManagerStore.selectNodes();
        close();
      },
    });
  }

  function close(): void {
    formErrorMessage.value = null;
    isSubmitting.value = false;
    uiStore.unzipModal = ModalState.Closed;
    uiStore.targetArchiveFilePath = null;
  }

  return {
    isOpen,
    archivePath,
    archiveName,
    extractMode,
    subfolderName,
    isSubmitting,
    formErrorMessage,
    submit,
    close,
  };
}
