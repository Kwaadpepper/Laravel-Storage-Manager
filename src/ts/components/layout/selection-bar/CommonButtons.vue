<script setup lang="ts">
defineOptions({
  name: 'CommonButtons',
});
import { useService } from '@ts/service-container';
import { ModalState, useConfigStore, useDiskStore, useUiStore } from '@ts/stores';
import { isDirectory, TreeNode } from '@ts/types';
import { CopyPlus, FolderOutput, Link, Pencil, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
  selectedNodes: TreeNode[];
}>();

const configStore = useConfigStore();
const diskStore = useDiskStore();
const uiStore = useUiStore();
const toastService = useService('toastService');

const isReadOnly = computed(() =>
  diskStore.currentDisk ? configStore.readOnlyDisks.includes(diskStore.currentDisk) : false
);

const hasMultipleSelection = computed(() => props.selectedNodes.length > 1);
const firstSelectedNode = computed<TreeNode | null>(
  () => props.selectedNodes[0] || null
);
const firstNodeIsDirectory = computed(
  () => firstSelectedNode.value !== null && isDirectory(firstSelectedNode.value)
);

function onCopyPathClick(): void {
  const paths = props.selectedNodes.map((n) => n.path).join('\n');
  navigator.clipboard.writeText(paths).catch(() => {});
  toastService.pushToast({ message: 'Path(s) copied to clipboard.', type: 'success' });
}

function onCopyLinkClick(): void {
  const url = new URL(globalThis.location.href);
  const disk = diskStore.currentDisk;
  const links = props.selectedNodes
    .map((n) => {
      const hashPath = `/${disk}${n.path}`;
      url.hash = hashPath.split('/').map(encodeURIComponent).join('/');
      return url.toString();
    })
    .join('\n');
  navigator.clipboard.writeText(links).catch(() => {});
  toastService.pushToast({ message: 'Link(s) copied to clipboard.', type: 'success' });
}

function onCopyPublicUrlClick(): void {
  const urls = props.selectedNodes
    .map((n) => (n as TreeNode & { publicUrl?: string }).publicUrl)
    .filter(Boolean)
    .join('\n');
  if (urls) {
    navigator.clipboard.writeText(urls).catch(() => {});
    toastService.pushToast({
      message: 'Public URL(s) copied to clipboard.',
      type: 'success',
    });
  } else {
    toastService.pushToast({ message: 'No public URL found.', type: 'warning' });
  }
}

function onClickRename(): void {
  if (props.selectedNodes.length !== 1 || !firstSelectedNode.value) {
    return;
  }
  if (firstNodeIsDirectory.value) {
    uiStore.setTargetDirectoryPath(firstSelectedNode.value.path);
    uiStore.setRenameDirectoryModal(ModalState.Opened);
  } else {
    uiStore.setTargetFilePath(firstSelectedNode.value.path);
    uiStore.setRenameFileModal(ModalState.Opened);
  }
}

function onClickDelete(): void {
  uiStore.setDeleteModal(ModalState.Opened);
}
</script>

<template>
  <template v-if="selectedNodes.length > 0">
    <button
      type="button"
      class="btn btn-ghost btn-xs"
      title="Copy To"
      :disabled="isReadOnly"
      @click="uiStore.setCopyModal(ModalState.Opened)"
    >
      <CopyPlus :size="14" />
      <span class="hidden sm:inline">Copy To</span>
    </button>

    <button
      type="button"
      class="btn btn-ghost btn-xs"
      title="Move To"
      :disabled="isReadOnly"
      @click="uiStore.setMoveModal(ModalState.Opened)"
    >
      <FolderOutput :size="14" />
      <span class="hidden sm:inline">Move To</span>
    </button>

    <button
      type="button"
      class="btn btn-ghost btn-xs"
      title="Rename"
      :disabled="hasMultipleSelection || isReadOnly"
      @click="onClickRename"
    >
      <Pencil :size="14" />
      <span class="hidden sm:inline">Rename</span>
    </button>

    <button
      type="button"
      class="btn btn-ghost btn-xs text-error"
      title="Delete"
      :disabled="isReadOnly"
      @click="onClickDelete"
    >
      <Trash2 :size="14" />
      <span class="hidden sm:inline">Delete</span>
    </button>

    <div class="dropdown dropdown-bottom">
      <div
        tabindex="0"
        role="button"
        class="btn btn-ghost btn-xs"
        title="Copy Links"
      >
        <Link :size="14" />
        <span class="hidden sm:inline">URL</span>
      </div>
      <ul
        tabindex="0"
        class="dropdown-content z-[50] menu p-2 shadow bg-base-100 rounded-box w-52"
      >
        <li><a @click="onCopyPathClick">Copy Path</a></li>
        <li><a @click="onCopyLinkClick">Copy Link</a></li>
        <li v-if="selectedNodes.some((n) => (n as TreeNode & { publicUrl?: string }).publicUrl)">
          <a @click="onCopyPublicUrlClick">Copy Public URL</a>
        </li>
      </ul>
    </div>
  </template>
</template>
