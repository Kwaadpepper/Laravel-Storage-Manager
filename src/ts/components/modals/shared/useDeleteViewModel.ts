import { computed, type ComputedRef } from 'vue';
import { useService } from '@ts/service-container';
import { ModalState, useFileManagerStore, useUiStore } from '@ts/stores';
import type { Path } from '@ts/types';

export interface DeleteViewModel {
  isOpen: ComputedRef<boolean>;
  itemCount: ComputedRef<number>;
  singleItemName: ComputedRef<string>;
  close: () => void;
  submit: () => void;
}

export function useDeleteViewModel(): DeleteViewModel {
  const fileManagerService = useService('fileManagerService');
  const eventQueueService = useService('eventQueueService');
  const uiStore = useUiStore();
  const fileManagerStore = useFileManagerStore();

  const selectedPaths = computed(() => Object.keys(fileManagerStore.selectedNodes) as Path[]);
  const itemCount = computed(() => selectedPaths.value.length);
  const singleItemName = computed(() =>
    itemCount.value === 1 ? fileManagerStore.selectedNodes[selectedPaths.value[0]]?.name ?? '' : ''
  );

  const isOpen = computed(() => uiStore.deleteModal === ModalState.Opened);

  function close(): void {
    uiStore.setDeleteModal(ModalState.Closed);
  }

  function submit(): void {
    if (itemCount.value === 0) return;

    const events = selectedPaths.value.map((path) => ({
      id: `del-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`,
      type: 'DELETE' as const,
      sourcePath: path,
      execute: async (): Promise<void> => {
        await fileManagerService.deleteItem(path, true);
      },
    }));

    eventQueueService.pushBatch(events);

    // Clear selection since items are being deleted
    fileManagerStore.selectNodes();
    close();
  }

  return {
    isOpen,
    itemCount,
    singleItemName,
    close,
    submit,
  };
}
