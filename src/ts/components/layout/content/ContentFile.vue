<script setup lang="ts">
defineOptions({
  name: 'ContentFile',
});
import FileSize from '@ts/components/shared/FileSize.vue';
import { useContextualMenuRegistration } from '@ts/components/shared/use-contextual-menu-registration';
import { useService } from '@ts/service-container';
import {
  ContextualMenuEntry,
  ModalState,
  toAnchorName,
  useConfigStore,
  useDiskStore,
  useFileManagerStore,
  usePickerStore,
  useUiStore,
} from '@ts/stores';
import { TreeNodeFile } from '@ts/types';
import FileIcon from '@ts/components/shared/FileIcon.vue';
import FileThumbnail from '@ts/components/shared/FileThumbnail.vue';
import { getFileTypeInfo } from '@ts/utils/file-type';
import { t } from '@ts/i18n';
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
const pickerStore = usePickerStore();
const downloadService = useService('downloadService');
const clipboardService = useService('clipboardService');
const toastService = useService('toastService');

const anchorName = computed(() => toAnchorName(props.item.path));
const fileType = computed(() => getFileTypeInfo(props.item.name, props.item.extension));
const isReadOnly = computed(() =>
  diskStore.currentDisk ? configStore.readOnlyDisks.includes(diskStore.currentDisk) : false
);

async function download(): Promise<void> {
  try {
    await downloadService.triggerDownload(props.item.path, props.item.name);
  } catch {
    toastService.pushToast({ message: t('download_failed'), type: 'error' });
  }
}

const entries = computed<ContextualMenuEntry[]>(() => [
  ...(pickerStore.canSelectFile(props.item)
    ? [
        {
          label: t('select'),
          onClick: () => {
            pickerStore.selectFile(props.item);
          },
        },
      ]
    : []),
  {
    label: t('view'),
    onClick: () => {
      uiStore.targetFilePath = props.item.path;
      uiStore.viewFileModal = ModalState.Opened;
    },
  },
  {
    label: t('properties'),
    onClick: () => {
      uiStore.targetFilePath = props.item.path;
      uiStore.fileMetadataModal = ModalState.Opened;
    },
  },
  ...(fileType.value.category === 'image' && !isReadOnly.value
    ? [
        {
          label: t('crop_image'),
          onClick: () => {
            uiStore.targetFilePath = props.item.path;
            uiStore.cropImageModal = ModalState.Opened;
          },
        },
      ]
    : []),
  { label: t('download'), onClick: () => void download() },
  ...(configStore.supportsZip && props.item.name.toLowerCase().endsWith('.zip')
    ? [
        {
          label: t('extract_archive'),
          onClick: () => {
            uiStore.targetArchiveFilePath = props.item.path;
            uiStore.unzipModal = ModalState.Opened;
          },
        },
      ]
    : []),
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
  ...(props.item.publicUrl
    ? [
        {
          label: t('copy_public_url'),
          onClick: () => {
            navigator.clipboard.writeText(props.item.publicUrl as string).catch(() => {});
            toastService.pushToast({
              message: t('public_url_copied'),
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
      uiStore.targetFilePath = props.item.path;
      uiStore.renameFileModal = ModalState.Opened;
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
    <FileThumbnail
      v-if="fileType.category === 'image' && configStore.supportsThumbnails"
      :item="item"
      :size="48"
    />
    <FileIcon
      v-else
      :name="item.name"
      :extension="item.extension"
      :size="36"
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
        <FileThumbnail
          v-if="fileType.category === 'image' && configStore.supportsThumbnails"
          :item="item"
          :size="18"
          class="rounded-sm"
        />
        <FileIcon
          v-else
          :name="item.name"
          :extension="item.extension"
          :size="16"
        />
        <span>{{ item.name }}</span>
      </span>
    </div>
    <div class="table-cell select-none p-2">
      <span class="badge badge-ghost badge-sm">{{ fileType.label }}</span>
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
