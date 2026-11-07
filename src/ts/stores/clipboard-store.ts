import type { Path } from '@ts/types';
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

export type ClipboardEntry = Path[];

const CLIPBOARD_ERROR_MESSAGE = 'Clipboard operations are not supported in this environment.';
const MAX_HISTORY_SIZE = 100;

function writeToSystemClipboard(text: string): void {
  if (globalThis.navigator?.clipboard) {
    globalThis.navigator.clipboard.writeText(text).catch((err) => {
      console.error(CLIPBOARD_ERROR_MESSAGE, err);
    });
  }
}

export const useClipboardStore = defineStore('clipboard', () => {
  const history = ref<ClipboardEntry[]>([]);
  const isCutMode = ref(false);
  const hasEntries = computed(() => history.value.length > 0);

  function setHasEntries(value: boolean): void {
    if (!value) {
      history.value = [];
    }
  }

  function setIsCutMode(value: boolean): void {
    isCutMode.value = value;
  }

  function setConsumingMode(isConsumingMode: boolean): void {
    isCutMode.value = isConsumingMode;
  }

  function getIsConsumingMode(): boolean {
    return isCutMode.value;
  }

  function cloneEntry(entry: ClipboardEntry): ClipboardEntry {
    return Array.from(entry.map((path) => `${path}` as Path));
  }

  function getEntries(): ClipboardEntry[] {
    const entries = history.value.map((val) => cloneEntry(val));
    if (isCutMode.value) {
      clearEntries();
    }
    return entries;
  }

  function getLastEntry(): ClipboardEntry | null {
    const last = history.value.at(-1);
    if (!last) return null;
    return cloneEntry(last);
  }

  function addEntry(...entry: ClipboardEntry): void {
    if (history.value.length >= MAX_HISTORY_SIZE) {
      history.value.shift();
    }
    history.value.push(entry);
    writeToSystemClipboard(entry.join('\n'));
  }

  function removeLastEntry(): void {
    const popped = history.value.pop();
    if (!popped) return;
    const newLast = history.value.at(-1);
    if (newLast) {
      writeToSystemClipboard(newLast.join('\n'));
    } else {
      writeToSystemClipboard('');
    }
  }

  function clearEntries(): void {
    history.value = [];
    isCutMode.value = false;
    writeToSystemClipboard('');
  }

  return {
    history,
    hasEntries,
    isCutMode,
    setHasEntries,
    setIsCutMode,
    setConsumingMode,
    getIsConsumingMode,
    getEntries,
    getLastEntry,
    addEntry,
    removeLastEntry,
    clearEntries,
  };
});

export type ClipboardStore = ReturnType<typeof useClipboardStore>;
