<script setup lang="ts">
defineOptions({
  name: 'ContentFile',
});
import FileSize from '@ts/components/shared/FileSize.vue';
import { useContextualMenuRegistration } from '@ts/components/shared/use-contextual-menu-registration';
import { useService } from '@ts/service-container';
import { ContextualMenuEntry, ModalState, toAnchorName, useConfigStore, useDiskStore, useFileManagerStore, useUiStore } from '@ts/stores';
import { TreeNodeFile } from '@ts/types';
import { File as FileIcon } from 'lucide-vue-next';
import { computed } from 'vue';

const props = withDefaults(
  defineProps<{
    item: TreeNodeFile;
    asTile?: boolean;
  }>(),
  {
    asTile: false,
  }
);

const configStore = useConfigStore();
const uiStore = useUiStore();
const fileManagerStore = useFileManagerStore();
const diskStore = useDiskStore();
const downloadService = useService('downloadService');
const clipboardService = useService('clipboardService');
const toastService = useService('toastService');

const anchorName = computed(() => toAnchorName(props.item.path));

async function download(): Promise<void> {
  try {
    const blob = await downloadService.downloadFile(props.item.path);
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = props.item.name;
    a.click();
    URL.revokeObjectURL(url);
  } catch {
    toastService.pushToast({ message: 'Failed to download file.', type: 'error' });
  }
}

const entries = computed<ContextualMenuEntry[]>(() => [
  {
    label: 'View',
    onClick: () => {
      uiStore.targetFilePath = props.item.path;
      uiStore.viewFileModal = ModalState.Opened;
    },
  },
  { label: 'Download', onClick: () => void download() },
  ...(configStore.supportsZip && props.item.name.toLowerCase().endsWith('.zip')
    ? [
        {
          label: 'Extract Archive...',
          onClick: () => {
            uiStore.targetArchiveFilePath = props.item.path;
            uiStore.unzipModal = ModalState.Opened;
          },
        },
      ]
    : []),
  { separator: true },
  {
    label: 'Cut',
    onClick: () => {
      const paths = Object.values(fileManagerStore.selectedNodes);
      const nodes = paths.length > 0 ? paths : [props.item];
      clipboardService.setConsumingMode(true);
      clipboardService.addEntry(...nodes.map((n) => n.path));
      toastService.pushToast({
        message: `${nodes.length} item(s) cut to clipboard.`,
        type: 'info',
      });
    },
  },
  {
    label: 'Copy',
    onClick: () => {
      const paths = Object.values(fileManagerStore.selectedNodes);
      const nodes = paths.length > 0 ? paths : [props.item];
      clipboardService.setConsumingMode(false);
      clipboardService.addEntry(...nodes.map((n) => n.path));
      toastService.pushToast({
        message: `${nodes.length} item(s) copied to clipboard.`,
        type: 'info',
      });
    },
  },
  { separator: true },
  {
    label: 'Copy Path',
    onClick: () => {
      navigator.clipboard.writeText(props.item.path).catch(() => {});
      toastService.pushToast({ message: 'Path copied to clipboard.', type: 'success' });
    },
  },
  {
    label: 'Copy Link',
    onClick: () => {
      const url = new URL(globalThis.location.href);
      const disk = diskStore.currentDisk;
      const hashPath = `/${disk}${props.item.path}`;
      url.hash = hashPath.split('/').map(encodeURIComponent).join('/');
      navigator.clipboard.writeText(url.toString()).catch(() => {});
      toastService.pushToast({ message: 'Link copied to clipboard.', type: 'success' });
    },
  },
  ...(props.item.publicUrl
    ? [
        {
          label: 'Copy Public URL',
          onClick: () => {
            navigator.clipboard.writeText(props.item.publicUrl as string).catch(() => {});
            toastService.pushToast({
              message: 'Public URL copied to clipboard.',
              type: 'success',
            });
          },
        },
      ]
    : []),
  { separator: true },
  ...(configStore.supportsZip
    ? [
        {
          label: 'Compress to Zip...',
          onClick: () => {
            if (!fileManagerStore.selectedNodes[props.item.path]) {
              fileManagerStore.selectNodes(props.item);
            }
            uiStore.zipModal = ModalState.Opened;
          },
        },
      ]
    : []),
  {
    label: 'Rename',
    onClick: () => {
      if (!fileManagerStore.selectedNodes[props.item.path]) {
        fileManagerStore.selectNodes(props.item);
      }
      uiStore.targetFilePath = props.item.path;
      uiStore.renameFileModal = ModalState.Opened;
    },
  },
  {
    label: 'Delete',
    onClick: () => {
      if (!fileManagerStore.selectedNodes[props.item.path]) {
        fileManagerStore.selectNodes(props.item);
      }
      uiStore.deleteModal = ModalState.Opened;
    },
  },
]);

useContextualMenuRegistration(anchorName, entries);
</script>

<template>
  <div
    v-if="asTile"
    data-selectable
    :data-contextual-menu="anchorName"
    :style="{ anchorName }"
    class="flex flex-col items-center gap-1 p-3 rounded-lg w-24 select-none"
  >
    <FileIcon
      :size="36"
      class="text-info"
    />
    <span class="text-xs text-center truncate w-full">{{ item.name }}</span>
    <span class="text-xs text-base-content/50"><FileSize :bytes="item.size" /></span>
    <span
      v-if="item.visibility"
      :class="`badge badge-xs mt-1 ${item.visibility === 'public' ? 'badge-success badge-outline' : 'badge-neutral badge-outline'}`"
    >
      {{ item.visibility }}
    </span>
  </div>

  <template v-else>
    <div
      data-selectable
      :data-contextual-menu="anchorName"
      tabindex="-1"
      :style="{ anchorName }"
      class="table-cell select-none p-2"
    >
      <span class="flex items-center gap-2">
        <FileIcon
          :size="16"
          class="text-info"
        />
        <span>{{ item.name }}</span>
      </span>
    </div>
    <div class="table-cell select-none p-2">
      <span class="badge badge-ghost badge-sm">File</span>
    </div>
    <div class="table-cell select-none p-2">
      <FileSize :bytes="item.size" />
    </div>
    <div class="table-cell select-none p-2">
      <span v-if="item.extension">{{ item.extension }}</span>
      <span
        v-else
        class="text-base-content/40"
      >N/A</span>
    </div>
    <div class="table-cell select-none p-2">
      <span
        v-if="item.visibility"
        :class="`badge badge-sm ${item.visibility === 'public' ? 'badge-success badge-outline' : 'badge-neutral badge-outline'}`"
      >
        {{ item.visibility }}
      </span>
    </div>
  </template>
</template>
