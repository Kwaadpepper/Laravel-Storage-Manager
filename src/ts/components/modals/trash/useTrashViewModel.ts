import { computed, onUnmounted, ref, watch, type ComputedRef, type Ref } from 'vue';
import { storeToRefs } from 'pinia';
import { useService } from '@ts/service-container';
import { ModalState, useDiskStore, useTrashStore, useUiStore, type TrashItemProcessingState } from '@ts/stores';
import type { TrashedItem } from '@ts/types';

export interface TrashViewModel {
  isOpen: ComputedRef<boolean>;
  items: Ref<TrashedItem[]>;
  filteredItems: ComputedRef<TrashedItem[]>;
  isLoading: Ref<boolean>;
  searchQuery: Ref<string>;
  selectedIds: Ref<string[]>;
  isAllSelected: ComputedRef<boolean>;
  isIndeterminate: ComputedRef<boolean>;
  confirmEmptyOpen: Ref<boolean>;
  isProcessing: ComputedRef<boolean>;
  isAnyProcessing: ComputedRef<boolean>;
  isEmptying: ComputedRef<boolean>;
  canRestoreSelected: ComputedRef<boolean>;
  canPurgeSelected: ComputedRef<boolean>;
  nonProcessingSelectedCount: ComputedRef<number>;
  getItemProcessingState: (id: string) => TrashItemProcessingState | null;
  isItemProcessing: (id: string) => boolean;
  currentDisk: ComputedRef<string | null>;
  close: () => void;
  fetchTrash: (showLoading?: boolean) => Promise<void>;
  toggleSelectAll: () => void;
  toggleSelectItem: (id: string) => void;
  isSelected: (id: string) => boolean;
  restoreItems: (ids: string[]) => void;
  purgeItems: (ids: string[]) => void;
  emptyTrash: () => void;
}

export function useTrashViewModel(): TrashViewModel {
  const trashService = useService('trashService');
  const toastService = useService('toastService');
  const eventQueueService = useService('eventQueueService');
  const uiStore = useUiStore();
  const diskStore = useDiskStore();
  const trashStore = useTrashStore();

  const isOpen = computed(() => uiStore.trashModal === ModalState.Opened);
  const { items } = storeToRefs(trashStore);
  const isLoading = ref(false);
  const searchQuery = ref('');
  const selectedIds = ref<string[]>([]);
  const confirmEmptyOpen = ref(false);

  const currentDisk = computed(() => diskStore.currentDisk);

  function getItemProcessingState(id: string): TrashItemProcessingState | null {
    return trashStore.getItemProcessingState(id);
  }

  function isItemProcessing(id: string): boolean {
    return trashStore.isItemProcessing(id);
  }

  const isAnyProcessing = computed(() => trashStore.isAnyProcessing);
  const isProcessing = computed(() => trashStore.isAnyProcessing);
  const isEmptying = computed(() => trashStore.isEmptying);

  const hasLockedItems = computed(() => {
    return (
      trashStore.isAnyProcessing ||
      items.value.some((item) => item.status === 'restoring' || item.status === 'purging')
    );
  });

  const canRestoreSelected = computed(
    () => selectedIds.value.length > 0 && selectedIds.value.some((id) => !trashStore.isItemProcessing(id))
  );

  const canPurgeSelected = computed(
    () => selectedIds.value.length > 0 && selectedIds.value.some((id) => !trashStore.isItemProcessing(id))
  );

  const nonProcessingSelectedCount = computed(
    () => selectedIds.value.filter((id) => !trashStore.isItemProcessing(id)).length
  );

  const filteredItems = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) {
      return items.value;
    }
    return items.value.filter(
      (item) => item.name.toLowerCase().includes(q) || item.originalPath.toLowerCase().includes(q)
    );
  });

  const isAllSelected = computed(() => {
    const selectable = filteredItems.value.filter((item) => !trashStore.isItemProcessing(item.id));
    if (selectable.length === 0) return false;
    return selectable.every((item) => selectedIds.value.includes(item.id));
  });

  const isIndeterminate = computed(() => {
    const selectable = filteredItems.value.filter((item) => !trashStore.isItemProcessing(item.id));
    if (selectable.length === 0) return false;
    const count = selectable.filter((item) => selectedIds.value.includes(item.id)).length;
    return count > 0 && count < selectable.length;
  });

  function close(): void {
    uiStore.trashModal = ModalState.Closed;
    selectedIds.value = [];
    searchQuery.value = '';
    confirmEmptyOpen.value = false;
  }

  let isFetching = false;
  async function fetchTrash(showLoading = true): Promise<void> {
    if (isFetching) return;
    isFetching = true;
    if (showLoading) {
      isLoading.value = true;
    }
    try {
      const res = await trashService.list();
      items.value = res.items;

      const resItemMap = new Map(res.items.map((i) => [i.id, i]));

      // Synchronize in-flight lock states from server
      for (const item of res.items) {
        if (item.status === 'restoring') {
          trashStore.setItemProcessing(item.id, 'restore');
        } else if (item.status === 'purging') {
          trashStore.setItemProcessing(item.id, 'purge');
        } else if (item.status === 'trashed' && trashStore.isItemProcessing(item.id)) {
          trashStore.clearItemProcessing(item.id, false);
        }
      }

      // If an item was processing in store but is no longer in server items (restored or purged),
      // clear its processing state
      for (const id of Object.keys(trashStore.processingMap)) {
        if (!resItemMap.has(id)) {
          trashStore.clearItemProcessing(id, false);
        }
      }

      const existingIds = new Set(res.items.map((i) => i.id));
      selectedIds.value = selectedIds.value.filter((id) => existingIds.has(id));
    } catch {
      if (showLoading) {
        toastService.pushToast({
          type: 'error',
          message: 'Failed to load trash items.',
        });
      }
    } finally {
      isFetching = false;
      if (showLoading) {
        isLoading.value = false;
      }
    }
  }

  let pollInterval: ReturnType<typeof setInterval> | null = null;

  function startPolling(): void {
    if (pollInterval !== null) return;
    pollInterval = setInterval(() => {
      if (isOpen.value && hasLockedItems.value) {
        void fetchTrash(false);
      } else {
        stopPolling();
      }
    }, 2000);
  }

  function stopPolling(): void {
    if (pollInterval !== null) {
      clearInterval(pollInterval);
      pollInterval = null;
    }
  }

  watch(
    [() => isOpen.value, hasLockedItems],
    ([open, locked]) => {
      if (open && locked) {
        startPolling();
      } else {
        stopPolling();
      }
    },
    { immediate: true }
  );

  watch(
    () => trashStore.refreshTrigger,
    () => {
      if (isOpen.value) {
        void fetchTrash(false);
      }
    }
  );

  watch(
    () => diskStore.currentDisk,
    () => {
      trashStore.reset();
      if (isOpen.value) {
        void fetchTrash(true);
      }
    }
  );

  onUnmounted(() => {
    stopPolling();
  });

  function toggleSelectAll(): void {
    const selectable = filteredItems.value.filter((item) => !trashStore.isItemProcessing(item.id));
    if (selectable.length === 0) return;

    if (isAllSelected.value) {
      const currentFilteredIds = new Set(selectable.map((i) => i.id));
      selectedIds.value = selectedIds.value.filter((id) => !currentFilteredIds.has(id));
    } else {
      const newSelected = new Set(selectedIds.value);
      for (const item of selectable) {
        newSelected.add(item.id);
      }
      selectedIds.value = Array.from(newSelected);
    }
  }

  function toggleSelectItem(id: string): void {
    const index = selectedIds.value.indexOf(id);
    if (index >= 0) {
      selectedIds.value.splice(index, 1);
    } else {
      selectedIds.value.push(id);
    }
  }

  function isSelected(id: string): boolean {
    return selectedIds.value.includes(id);
  }

  function restoreItems(ids: string[]): void {
    const targetIds = ids.filter((id) => !trashStore.isItemProcessing(id));
    if (targetIds.length === 0) return;

    for (const id of targetIds) {
      trashStore.setItemProcessing(id, 'restore');
    }

    selectedIds.value = selectedIds.value.filter((id) => !targetIds.includes(id));

    const count = targetIds.length;
    let completedCount = 0;

    const events = targetIds.map((id) => {
      const item = items.value.find((i) => i.id === id);
      const originalPath = item?.originalPath ?? id;
      const name = item?.name ?? id;

      return {
        id: `restore-${id}-${Date.now()}-${Math.random().toString(36).substring(2, 7)}`,
        type: 'RESTORE' as const,
        sourcePath: originalPath,
        execute: async (): Promise<void> => {
          try {
            await trashService.restore([id]);
            trashStore.removeItem(id);
            completedCount++;

            if (completedCount === count) {
              toastService.pushToast({
                type: 'success',
                message:
                  count === 1
                    ? `"${name}" restored successfully.`
                    : `${count} items restored successfully.`,
              });
            }
          } catch (err: unknown) {
            toastService.pushToast({
              type: 'error',
              message: `Failed to restore "${name}": ${err instanceof Error ? err.message : 'Unknown error'}`,
            });
            throw err;
          } finally {
            trashStore.clearItemProcessing(id);
          }
        },
      };
    });

    eventQueueService.pushBatch(events);
  }

  function purgeItems(ids: string[]): void {
    const targetIds = ids.filter((id) => !trashStore.isItemProcessing(id));
    if (targetIds.length === 0) return;

    for (const id of targetIds) {
      trashStore.setItemProcessing(id, 'purge');
    }

    selectedIds.value = selectedIds.value.filter((id) => !targetIds.includes(id));

    const count = targetIds.length;
    let completedCount = 0;

    const events = targetIds.map((id) => {
      const item = items.value.find((i) => i.id === id);
      const originalPath = item?.originalPath ?? id;
      const name = item?.name ?? id;

      return {
        id: `purge-${id}-${Date.now()}-${Math.random().toString(36).substring(2, 7)}`,
        type: 'PURGE' as const,
        sourcePath: originalPath,
        execute: async (): Promise<void> => {
          try {
            await trashService.purge([id]);
            trashStore.removeItem(id);
            completedCount++;

            if (completedCount === count) {
              toastService.pushToast({
                type: 'success',
                message:
                  count === 1
                    ? `"${name}" permanently deleted.`
                    : `${count} items permanently deleted.`,
              });
            }
          } catch (err: unknown) {
            toastService.pushToast({
              type: 'error',
              message: `Failed to delete "${name}": ${err instanceof Error ? err.message : 'Unknown error'}`,
            });
            throw err;
          } finally {
            trashStore.clearItemProcessing(id);
          }
        },
      };
    });

    eventQueueService.pushBatch(events);
  }

  function emptyTrash(): void {
    if (trashStore.isAnyProcessing) return;
    confirmEmptyOpen.value = false;
    trashStore.isEmptying = true;

    const event = {
      id: `purge-empty-${Date.now()}-${Math.random().toString(36).substring(2, 7)}`,
      type: 'PURGE' as const,
      sourcePath: 'Trash',
      destinationPath: 'All items',
      execute: async (): Promise<void> => {
        try {
          await trashService.emptyTrash();
          trashStore.setItems([]);
          selectedIds.value = [];
          toastService.pushToast({
            type: 'success',
            message: 'Trash emptied successfully.',
          });
        } catch (err: unknown) {
          toastService.pushToast({
            type: 'error',
            message: `Failed to empty trash: ${err instanceof Error ? err.message : 'Unknown error'}`,
          });
          throw err;
        } finally {
          trashStore.isEmptying = false;
          trashStore.requestRefresh();
        }
      },
    };

    eventQueueService.push(event);
  }

  watch(
    () => isOpen.value,
    (open) => {
      if (open) {
        void fetchTrash(true);
      } else {
        stopPolling();
      }
    },
    { immediate: true }
  );

  return {
    isOpen,
    items,
    filteredItems,
    isLoading,
    searchQuery,
    selectedIds,
    isAllSelected,
    isIndeterminate,
    confirmEmptyOpen,
    isProcessing,
    isAnyProcessing,
    isEmptying,
    canRestoreSelected,
    canPurgeSelected,
    nonProcessingSelectedCount,
    getItemProcessingState,
    isItemProcessing,
    currentDisk,
    close,
    fetchTrash,
    toggleSelectAll,
    toggleSelectItem,
    isSelected,
    restoreItems,
    purgeItems,
    emptyTrash,
  };
}
