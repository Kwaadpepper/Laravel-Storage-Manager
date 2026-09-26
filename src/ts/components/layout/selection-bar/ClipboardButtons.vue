<script setup lang="ts">
defineOptions({
  name: 'ClipboardButtons',
});
import { useService } from '@ts/service-container';
import { useClipboardStore, useFileManagerStore } from '@ts/stores';
import { TreeNode } from '@ts/types';
import { ClipboardCopy, ClipboardPaste, Scissors } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
  selectedNodes: TreeNode[];
}>();

const toastService = useService('toastService');
const clipboardService = useService('clipboardService');
const fileManagerService = useService('fileManagerService');
const eventQueueService = useService('eventQueueService');

const fileManagerStore = useFileManagerStore();
const clipboardStore = useClipboardStore();

const hasSelection = computed(() => props.selectedNodes.length > 0);

function onClickCopy(): void {
  if (props.selectedNodes.length === 0) return;
  clipboardService.setConsumingMode(false);
  clipboardService.addEntry(...props.selectedNodes.map((node) => node.path));
  toastService.pushToast({
    message: `${props.selectedNodes.length} item(s) copied to clipboard.`,
    type: 'success',
  });
}

function onClickCut(): void {
  if (props.selectedNodes.length === 0) return;
  clipboardService.setConsumingMode(true);
  clipboardService.addEntry(...props.selectedNodes.map((node) => node.path));
  toastService.pushToast({
    message: `${props.selectedNodes.length} item(s) cut to clipboard.`,
    type: 'success',
  });
}

async function onClickPaste(): Promise<void> {
  const isCut = clipboardService.getIsConsumingMode();
  const entry = clipboardService.getLastEntry();
  if (!entry || entry.length === 0) {
    toastService.pushToast({ message: 'Clipboard is empty.', type: 'info' });
    return;
  }

  const events = entry.map((path) => ({
    id: `${isCut ? 'move' : 'copy'}-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`,
    type: (isCut ? 'MOVE' : 'COPY') as 'MOVE' | 'COPY',
    sourcePath: path,
    destinationPath: fileManagerStore.currentPath,
    execute: async () => {
      if (isCut) {
        await fileManagerService.move(path, fileManagerStore.currentPath);
      } else {
        await fileManagerService.copy(path, fileManagerStore.currentPath);
      }
    },
  }));

  eventQueueService.pushBatch(events);
  clipboardService.clearEntries();
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
