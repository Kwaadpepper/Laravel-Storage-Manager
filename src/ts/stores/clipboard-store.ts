import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useClipboardStore = defineStore('clipboard', () => {
  const hasEntries = ref(false);
  const isCutMode = ref(false);

  function setHasEntries(value: boolean): void {
    hasEntries.value = value;
  }

  function setIsCutMode(value: boolean): void {
    isCutMode.value = value;
  }

  return {
    hasEntries,
    isCutMode,
    setHasEntries,
    setIsCutMode,
  };
});

export type ClipboardStore = ReturnType<typeof useClipboardStore>;
