<script setup lang="ts">
defineOptions({
  name: 'ContentDirectory',
});
import { useContextualMenuRegistration } from '@ts/components/shared/use-contextual-menu-registration';
import { useService } from '@ts/service-container';
import { ContextualMenuEntry, ModalState, toAnchorName, useClipboardStore, useConfigStore, useDiskStore, useFileManagerStore, useUiStore } from '@ts/stores';
import { rootPath, TreeNodeDirectory } from '@ts/types';
import { t } from '@ts/i18n';
import { Folder, FolderOpen } from 'lucide-vue-next';
import { computed } from 'vue';

const props = withDefaults(
  defineProps<{
    item: TreeNodeDirectory;
    asTile?: boolean;
  }>(),
  {
    asTile: false,
  }
);

const configStore = useConfigStore();
const uiStore = useUiStore();
const fileManagerStore = useFileManagerStore();
const clipboardStore = useClipboardStore();
const diskStore = useDiskStore();

const navigationService = useService('navigationService');
const clipboardService = useService('clipboardService');
const fileManagerService = useService('fileManagerService');
const eventQueueService = useService('eventQueueService');
const toastService = useService('toastService');

const anchorName = computed(() => toAnchorName(props.item.path));
const isRoot = computed(() => props.item.path === rootPath());

const entries = computed<ContextualMenuEntry[]>(() => [
  { label: t('open'), onClick: () => navigationService.navigateTo(props.item.path) },
  { separator: true },
  {
    label: t('cut'),
    onClick: () => {
      const paths = Object.values(fileManagerStore.selectedNodes);
      const nodes = paths.length > 0 ? paths : [props.item];
      clipboardService.setConsumingMode(true);
      clipboardService.addEntry(...nodes.map((n) => n.path));
      toastService.pushToast({
        message: t('items_cut', { count: nodes.length }),
        type: 'info',
      });
    },
  },
  {
    label: t('copy'),
    onClick: () => {
      const paths = Object.values(fileManagerStore.selectedNodes);
      const nodes = paths.length > 0 ? paths : [props.item];
      clipboardService.setConsumingMode(false);
      clipboardService.addEntry(...nodes.map((n) => n.path));
      toastService.pushToast({
        message: t('items_copied', { count: nodes.length }),
        type: 'info',
      });
    },
  },
  ...(clipboardStore.hasEntries
    ? [
        {
          label: t('paste'),
          onClick: async () => {
            const isCut = clipboardService.getIsConsumingMode();
            const entry = clipboardService.getLastEntry();
            if (!entry || entry.length === 0) {
              toastService.pushToast({ message: t('clipboard_empty'), type: 'info' });
              return;
            }

            const events = entry.map((path) => ({
              id: `${isCut ? 'move' : 'copy'}-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`,
              type: (isCut ? 'MOVE' : 'COPY') as 'MOVE' | 'COPY',
              sourcePath: path,
              destinationPath: props.item.path,
              execute: async () => {
                if (isCut) {
                  await fileManagerService.move(path, props.item.path);
                } else {
                  await fileManagerService.copy(path, props.item.path);
                }
              },
            }));

            eventQueueService.pushBatch(events);
            clipboardService.clearEntries();
          },
        },
      ]
    : []),
  { separator: true },
  {
    label: t('copy_path'),
    onClick: () => {
      navigator.clipboard.writeText(props.item.path).catch(() => {});
      toastService.pushToast({ message: t('path_copied'), type: 'success' });
    },
  },
  {
    label: t('copy_link'),
    onClick: () => {
      const url = new URL(globalThis.location.href);
      const disk = diskStore.currentDisk;
      const hashPath = `/${disk}${props.item.path}`;
      url.hash = hashPath.split('/').map(encodeURIComponent).join('/');
      navigator.clipboard.writeText(url.toString()).catch(() => {});
      toastService.pushToast({ message: t('link_copied'), type: 'success' });
    },
  },
  { separator: true },
  ...(configStore.supportsZip
    ? [
        {
          label: t('compress_to_zip'),
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
    label: t('rename'),
    onClick: () => {
      if (!fileManagerStore.selectedNodes[props.item.path]) {
        fileManagerStore.selectNodes(props.item);
      }
      uiStore.targetDirectoryPath = props.item.path;
      uiStore.renameDirectoryModal = ModalState.Opened;
    },
  },
  {
    label: t('delete'),
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
    <Folder
      v-if="isRoot"
      :size="36"
      class="text-warning"
    />
    <FolderOpen
      v-else
      :size="36"
      class="text-warning"
    />
    <span class="text-xs text-center truncate w-full">{{ isRoot ? 'root' : item.name }}</span>
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
        <Folder
          v-if="item.path === rootPath()"
          :size="16"
          class="text-warning"
        />
        <FolderOpen
          v-else
          :size="16"
          class="text-warning"
        />
        <span>{{ item.path === rootPath() ? 'root' : item.name }}</span>
      </span>
    </div>
    <div class="table-cell select-none p-2">
      <span class="badge badge-ghost badge-sm">Directory</span>
    </div>
    <div class="table-cell select-none p-2">
      -
    </div>
    <div class="table-cell select-none p-2">
      -
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
