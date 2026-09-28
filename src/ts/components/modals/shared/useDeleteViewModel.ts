import { computed, ref, type ComputedRef, type Ref } from 'vue';
import { useService } from '@ts/service-container';
import { ModalState, useConfigStore, useFileManagerStore, useUiStore } from '@ts/stores';
import type { Path } from '@ts/types';

export interface DeleteViewModel {
  isOpen: ComputedRef<boolean>;
  itemCount: ComputedRef<number>;
  singleItemName: ComputedRef<string>;
  permanentDelete: Ref<boolean>;
  supportsTrash: ComputedRef<boolean>;
  close: () => void;
  submit: () => void;
}

export function useDeleteViewModel(): DeleteViewModel {
  const fileManagerService = useService('fileManagerService');
  const eventQueueService = useService('eventQueueService');
  const toastService = useService('toastService');
  const trashService = useService('trashService');
  const navigationService = useService('navigationService');
  const uiStore = useUiStore();
  const configStore = useConfigStore();
  const fileManagerStore = useFileManagerStore();

  const permanentDelete = ref(false);
  const supportsTrash = computed(() => configStore.supportsTrash);

  const selectedPaths = computed(() => Object.keys(fileManagerStore.selectedNodes) as Path[]);
  const itemCount = computed(() => selectedPaths.value.length);
  const singleItemName = computed(() =>
    itemCount.value === 1 ? fileManagerStore.selectedNodes[selectedPaths.value[0]]?.name ?? '' : ''
  );

  const isOpen = computed(() => uiStore.deleteModal === ModalState.Opened);

  function close(): void {
    uiStore.deleteModal = ModalState.Closed;
    permanentDelete.value = false;
  }

  function submit(): void {
    if (itemCount.value === 0) return;

    const force = permanentDelete.value || !configStore.supportsTrash;
    const itemName = singleItemName.value;
    const count = itemCount.value;
    const trashedIds: string[] = [];
    let completedCount = 0;

    const events = selectedPaths.value.map((path) => ({
      id: `del-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`,
      type: 'DELETE' as const,
      sourcePath: path,
      execute: async (): Promise<void> => {
        const res = await fileManagerService.deleteItem(path, force);
        if (!force && res && typeof res === 'object' && res.trashed && res.trashId) {
          trashedIds.push(res.trashId);
        }
        completedCount++;

        if (completedCount === count && trashedIds.length > 0) {
          toastService.pushToast(
            {
              type: 'info',
              title: 'Moved to Trash',
              message: trashedIds.length === 1
                ? `"${itemName}" moved to trash.`
                : `${trashedIds.length} items moved to trash.`,
              action: {
                label: 'Undo',
                onClick: async (): Promise<void> => {
                  await trashService.restore(trashedIds);
                  await navigationService.refreshCurrentPath();
                  toastService.pushToast({
                    type: 'success',
                    message: 'Restored successfully.',
                  });
                },
              },
            },
            { timeout: 8000 }
          );
        }
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
    permanentDelete,
    supportsTrash,
    close,
    submit,
  };
}
