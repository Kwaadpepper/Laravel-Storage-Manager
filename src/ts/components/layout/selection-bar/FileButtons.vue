<script setup lang="ts">
defineOptions({
  name: 'FileButtons',
});
import { useService } from '@ts/service-container';
import { ModalState, useConfigStore, useUiStore } from '@ts/stores';
import { isFile, TreeNodeDirectory, TreeNodeFile } from '@ts/types';
import { ArchiveRestore, Download, Eye, Info } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
  selectedNodes: (TreeNodeDirectory | TreeNodeFile)[];
}>();

const configStore = useConfigStore();
const uiStore = useUiStore();
const downloadService = useService('downloadService');
const toastService = useService('toastService');

const hasMultipleSelection = computed(() => props.selectedNodes.length > 1);
const firstSelectedNode = computed<TreeNodeDirectory | TreeNodeFile | null>(
  () => props.selectedNodes[0] || null
);
const firstNodeIsFile = computed(
  () => firstSelectedNode.value !== null && isFile(firstSelectedNode.value)
);
const isZipFile = computed(
  () => firstNodeIsFile.value && (firstSelectedNode.value?.name.toLowerCase().endsWith('.zip') ?? false)
);

function onClickExtract(): void {
  if (firstSelectedNode.value) {
    uiStore.targetArchiveFilePath = firstSelectedNode.value.path;
    uiStore.unzipModal = ModalState.Opened;
  }
}

function onClickView(): void {
  const node = firstSelectedNode.value;
  if (node === null || !isFile(node)) {
    return;
  }
  uiStore.targetFilePath = node.path;
  uiStore.viewFileModal = ModalState.Opened;
}

function onClickProperties(): void {
  const node = firstSelectedNode.value;
  if (node === null || !isFile(node)) {
    return;
  }
  uiStore.targetFilePath = node.path;
  uiStore.fileMetadataModal = ModalState.Opened;
}

async function onClickDownload(): Promise<void> {
  try {
    const node = firstSelectedNode.value;
    if (node === null || !isFile(node)) {
      return;
    }
    const blob = await downloadService.downloadFile(node.path);
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = node.name;
    a.click();
    URL.revokeObjectURL(url);
  } catch {
    toastService.pushToast({ message: 'Failed to download file.', type: 'error' });
  }
}
</script>

<template>
  <template v-if="!hasMultipleSelection && firstNodeIsFile">
    <button
      type="button"
      class="btn btn-ghost btn-xs"
      title="View"
      @click="onClickView"
    >
      <Eye :size="14" />
      <span class="hidden sm:inline">View</span>
    </button>
    <button
      type="button"
      class="btn btn-ghost btn-xs"
      title="Properties"
      @click="onClickProperties"
    >
      <Info :size="14" />
      <span class="hidden sm:inline">Properties</span>
    </button>
    <button
      v-if="configStore.supportsZip && isZipFile"
      type="button"
      class="btn btn-ghost btn-xs"
      title="Extract Archive"
      @click="onClickExtract"
    >
      <ArchiveRestore :size="14" />
      <span class="hidden sm:inline">Extract</span>
    </button>
    <button
      type="button"
      class="btn btn-ghost btn-xs"
      title="Download"
      @click="onClickDownload"
    >
      <Download :size="14" />
      <span class="hidden sm:inline">Download</span>
    </button>
  </template>
</template>
