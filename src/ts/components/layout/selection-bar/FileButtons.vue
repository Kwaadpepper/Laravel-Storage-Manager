<script setup lang="ts">
defineOptions({
  name: 'FileButtons',
});
import { useDownload } from '@ts/composables';
import { ModalState, useConfigStore, usePickerStore, useToastStore, useUiStore } from '@ts/stores';
import { isFile, TreeNodeDirectory, TreeNodeFile } from '@ts/types';
import { t } from '@ts/i18n';
import { ArchiveRestore, Check, Download, Eye, Info } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
  selectedNodes: (TreeNodeDirectory | TreeNodeFile)[];
}>();

const configStore = useConfigStore();
const uiStore = useUiStore();
const pickerStore = usePickerStore();
const toastStore = useToastStore();
const { triggerDownload } = useDownload();

const hasMultipleSelection = computed(() => props.selectedNodes.length > 1);
const firstSelectedNode = computed<TreeNodeDirectory | TreeNodeFile | null>(
  () => props.selectedNodes[0] || null
);
const firstNodeIsFile = computed(
  () => firstSelectedNode.value !== null && isFile(firstSelectedNode.value)
);
const canSelect = computed(
  () =>
    firstSelectedNode.value !== null &&
    isFile(firstSelectedNode.value) &&
    pickerStore.canSelectFile(firstSelectedNode.value)
);
const isZipFile = computed(
  () => firstNodeIsFile.value && (firstSelectedNode.value?.name.toLowerCase().endsWith('.zip') ?? false)
);

function onClickSelect(): void {
  const node = firstSelectedNode.value;
  if (node !== null && isFile(node)) {
    pickerStore.selectFile(node);
  }
}

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
    await triggerDownload(node.path, node.name);
  } catch {
    toastStore.pushToast({ message: t('download_failed'), type: 'error' });
  }
}
</script>

<template>
  <template v-if="!hasMultipleSelection && firstNodeIsFile">
    <button
      v-if="canSelect"
      type="button"
      class="btn btn-primary btn-xs"
      :title="t('select')"
      @click="onClickSelect"
    >
      <Check :size="14" />
      <span class="hidden sm:inline">{{ t('select') }}</span>
    </button>
    <button
      type="button"
      class="btn btn-ghost btn-xs"
      :title="t('view')"
      @click="onClickView"
    >
      <Eye :size="14" />
      <span class="hidden sm:inline">{{ t('view') }}</span>
    </button>
    <button
      type="button"
      class="btn btn-ghost btn-xs"
      :title="t('properties')"
      @click="onClickProperties"
    >
      <Info :size="14" />
      <span class="hidden sm:inline">{{ t('properties') }}</span>
    </button>
    <button
      v-if="configStore.supportsZip && isZipFile"
      type="button"
      class="btn btn-ghost btn-xs"
      :title="t('extract_archive')"
      @click="onClickExtract"
    >
      <ArchiveRestore :size="14" />
      <span class="hidden sm:inline">{{ t('extract') }}</span>
    </button>
    <button
      type="button"
      class="btn btn-ghost btn-xs"
      :title="t('download')"
      @click="onClickDownload"
    >
      <Download :size="14" />
      <span class="hidden sm:inline">{{ t('download') }}</span>
    </button>
  </template>
</template>
