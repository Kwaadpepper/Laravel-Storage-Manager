<script setup lang="ts">
defineOptions({
  name: 'ClipboardButtons',
});
import { useEventQueue, useFileManager } from '@ts/composables';
import { useClipboardStore, useFileManagerStore, useToastStore } from '@ts/stores';
import { TreeNode } from '@ts/types';
import { ClipboardCopy, ClipboardPaste, Scissors } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
  selectedNodes: TreeNode[];
}>();

const toastStore = useToastStore();
const fileManager = useFileManager();
const eventQueue = useEventQueue();
const fileManagerStore = useFileManagerStore();
const clipboardStore = useClipboardStore();

const hasSelection = computed(() => props.selectedNodes.length > 0);

function onClickCopy(): void {
  if (props.selectedNodes.length === 0) return;
  clipboardStore.setConsumingMode(false);
  clipboardStore.addEntry(...props.selectedNodes.map((node) => node.path));
  toastStore.pushToast({
    message: `${props.selectedNodes.length} item(s) copied to clipboard.`,
    type: 'success',
  });
}

function onClickCut(): void {
  if (props.selectedNodes.length === 0) return;
  clipboardStore.setConsumingMode(true);
  clipboardStore.addEntry(...props.selectedNodes.map((node) => node.path));
  toastStore.pushToast({
    message: `${props.selectedNodes.length} item(s) cut to clipboard.`,
    type: 'success',
  });
}

async function onClickPaste(): Promise<void> {
  const isCut = clipboardStore.getIsConsumingMode();
  const entry = clipboardStore.getLastEntry();
  if (!entry || entry.length === 0) {
    toastStore.pushToast({ message: 'Clipboard is empty.', type: 'info' });
    return;
  }

  const events = entry.map((path) => ({
    id: `${isCut ? 'move' : 'copy'}-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`,
    type: (isCut ? 'MOVE' : 'COPY') as 'MOVE' | 'COPY',
    sourcePath: path,
    destinationPath: fileManagerStore.currentPath,
    execute: async () => {
      if (isCut) {
        await fileManager.move(path, fileManagerStore.currentPath);
      } else {
        await fileManager.copy(path, fileManagerStore.currentPath);
      }
    },
  }));

  eventQueue.pushBatch(events);
  clipboardStore.clearEntries();
}
</script>

<template>
  <template v-if="hasSelection">
    <button
      type="button"
      class="btn btn-ghost btn-xs"
      title="Copy"
      @click="onClickCopy"
    >
      <ClipboardCopy :size="14" />
      <span class="hidden sm:inline">Copy</span>
    </button>

    <button
      type="button"
      class="btn btn-ghost btn-xs"
      title="Cut"
      @click="onClickCut"
    >
      <Scissors :size="14" />
      <span class="hidden sm:inline">Cut</span>
    </button>
  </template>

  <button
    v-if="clipboardStore.hasEntries"
    type="button"
    class="btn btn-ghost btn-xs"
    title="Paste"
    @click="onClickPaste"
  >
    <ClipboardPaste :size="14" />
    <span class="hidden sm:inline">Paste</span>
  </button>
</template>
