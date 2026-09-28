import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import type { TrashedItem } from '@ts/types';

export type TrashItemProcessingState = 'restore' | 'purge';

export const useTrashStore = defineStore('trash', () => {
  const items = ref<TrashedItem[]>([]);
  const processingMap = ref<Record<string, TrashItemProcessingState>>({});
  const isEmptying = ref(false);
  const refreshTrigger = ref(0);

  const isAnyProcessing = computed(
    () => Object.keys(processingMap.value).length > 0 || isEmptying.value
  );

  function requestRefresh(): void {
    refreshTrigger.value++;
  }

  function setItems(newItems: TrashedItem[]): void {
    items.value = newItems;
  }

  function removeItem(id: string): void {
    items.value = items.value.filter((i) => i.id !== id);
  }

  function setItemProcessing(id: string, state: TrashItemProcessingState): void {
    processingMap.value = { ...processingMap.value, [id]: state };
  }

  function clearItemProcessing(id: string, notify = true): void {
    if (id in processingMap.value) {
      const next = { ...processingMap.value };
      delete next[id];
      processingMap.value = next;
      if (notify) {
        requestRefresh();
      }
    }
  }

  function getItemProcessingState(id: string): TrashItemProcessingState | null {
    return processingMap.value[id] ?? null;
  }

  function isItemProcessing(id: string): boolean {
    return Boolean(processingMap.value[id]);
  }

  function clearAllProcessing(): void {
    processingMap.value = {};
    isEmptying.value = false;
    requestRefresh();
  }

  function reset(): void {
    items.value = [];
    processingMap.value = {};
    isEmptying.value = false;
    requestRefresh();
  }

  return {
    items,
    processingMap,
    isEmptying,
    refreshTrigger,
    isAnyProcessing,
    setItems,
    removeItem,
    requestRefresh,
    setItemProcessing,
    clearItemProcessing,
    getItemProcessingState,
    isItemProcessing,
    clearAllProcessing,
    reset,
  };
});

export type TrashStore = ReturnType<typeof useTrashStore>;
