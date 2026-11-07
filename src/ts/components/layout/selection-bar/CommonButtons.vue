<script setup lang="ts">
defineOptions({
  name: 'CommonButtons',
});
import { ModalState, useConfigStore, useDiskStore, useToastStore, useUiStore } from '@ts/stores';
import { isDirectory, TreeNode } from '@ts/types';
import { t } from '@ts/i18n';
import { Archive, CopyPlus, FolderOutput, Link, Pencil, SpellCheck, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
  selectedNodes: TreeNode[];
}>();

const configStore = useConfigStore();
const diskStore = useDiskStore();
const uiStore = useUiStore();
const toastStore = useToastStore();

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
  toastStore.pushToast({ message: t('paths_copied'), type: 'success' });
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
  toastStore.pushToast({ message: t('links_copied'), type: 'success' });
}

function onCopyPublicUrlClick(): void {
  const urls = props.selectedNodes
    .map((n) => (n as TreeNode & { publicUrl?: string }).publicUrl)
    .filter(Boolean)
    .join('\n');
  if (urls) {
    navigator.clipboard.writeText(urls).catch(() => {});
    toastStore.pushToast({
      message: t('public_url_copied'),
      type: 'success',
    });
  } else {
    toastStore.pushToast({ message: t('no_public_url'), type: 'warning' });
  }
}

function onClickRename(): void {
  if (props.selectedNodes.length !== 1 || !firstSelectedNode.value) {
    return;
  }
  if (firstNodeIsDirectory.value) {
    uiStore.targetDirectoryPath = firstSelectedNode.value.path;
    uiStore.renameDirectoryModal = ModalState.Opened;
  } else {
    uiStore.targetFilePath = firstSelectedNode.value.path;
    uiStore.renameFileModal = ModalState.Opened;
  }
}

function onClickDelete(): void {
  uiStore.deleteModal = ModalState.Opened;
}
</script>

<template>
  <template v-if="selectedNodes.length > 0">
    <button
      type="button"
      class="btn btn-ghost btn-xs"
      :title="t('copy_to')"
      :disabled="isReadOnly"
      @click="uiStore.copyModal = ModalState.Opened"
    >
      <CopyPlus :size="14" />
      <span class="hidden sm:inline">{{ t('copy_to') }}</span>
    </button>

    <button
      type="button"
      class="btn btn-ghost btn-xs"
      :title="t('move_to')"
      :disabled="isReadOnly"
      @click="uiStore.moveModal = ModalState.Opened"
    >
      <FolderOutput :size="14" />
      <span class="hidden sm:inline">{{ t('move_to') }}</span>
    </button>

    <button
      v-if="!hasMultipleSelection"
      type="button"
      class="btn btn-ghost btn-xs"
      :title="t('rename')"
      :disabled="isReadOnly"
      @click="onClickRename"
    >
      <Pencil :size="14" />
      <span class="hidden sm:inline">{{ t('rename') }}</span>
    </button>

    <button
      v-else
      type="button"
      class="btn btn-ghost btn-xs"
      :title="t('bulk_rename')"
      :disabled="isReadOnly"
      @click="uiStore.bulkRenameModal = ModalState.Opened"
    >
      <SpellCheck :size="14" />
      <span class="hidden sm:inline">{{ t('bulk_rename') }}</span>
    </button>

    <button
      v-if="configStore.supportsZip"
      type="button"
      class="btn btn-ghost btn-xs"
      :title="t('compress_to_zip')"
      :disabled="isReadOnly"
      @click="uiStore.zipModal = ModalState.Opened"
    >
      <Archive :size="14" />
      <span class="hidden sm:inline">{{ t('zip') }}</span>
    </button>

    <button
      type="button"
      class="btn btn-ghost btn-xs text-error"
      :title="t('delete')"
      :disabled="isReadOnly"
      @click="onClickDelete"
    >
      <Trash2 :size="14" />
      <span class="hidden sm:inline">{{ t('delete') }}</span>
    </button>

    <div class="dropdown dropdown-bottom">
      <div
        tabindex="0"
        role="button"
        class="btn btn-ghost btn-xs"
        :title="t('url')"
      >
        <Link :size="14" />
        <span class="hidden sm:inline">{{ t('url') }}</span>
      </div>
      <ul
        tabindex="0"
        class="dropdown-content z-[50] menu p-2 shadow bg-base-100 rounded-box w-52"
      >
        <li><a @click="onCopyPathClick">{{ t('copy_path') }}</a></li>
        <li><a @click="onCopyLinkClick">{{ t('copy_link') }}</a></li>
        <li v-if="selectedNodes.some((n) => (n as TreeNode & { publicUrl?: string }).publicUrl)">
          <a @click="onCopyPublicUrlClick">{{ t('copy_public_url') }}</a>
        </li>
      </ul>
    </div>
  </template>
</template>
