<script setup lang="ts">
defineOptions({
  name: 'TreeView',
});
import DiskSelector from '@ts/components/shared/DiskSelector.vue';
import DiskUsageWidget from './DiskUsageWidget.vue';
import TreeNodeItem from '@ts/components/layout/tree-view/TreeNodeItem.vue';
import { useService } from '@ts/service-container';
import { ModalState, useConfigStore, useDiskStore, useFileManagerStore, useTreeStore, useUiStore } from '@ts/stores';
import { rootPath } from '@ts/types';
import { t } from '@ts/i18n';
import { ChevronDown, ChevronRight, Folder, FolderOpen, Trash2 } from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';

const navigationService = useService('navigationService');
const diskService = useService('diskService');
const fileManagerStore = useFileManagerStore();
const diskStore = useDiskStore();
const treeStore = useTreeStore();
const uiStore = useUiStore();
const configStore = useConfigStore();

const scrollRef = ref<HTMLDivElement | null>(null);
const rootState = computed(() => treeStore.nodes[rootPath()]);
const rootActive = computed(() => fileManagerStore.currentPath === rootPath());

watch(
  () => fileManagerStore.currentPath,
  async (path) => {
    await nextTick();
    const el = scrollRef.value?.querySelector<HTMLElement>(`[data-path="${path}"]`);
    if (el) {
      el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }
  }
);

watch(
  () => diskStore.currentDisk,
  (disk) => {
    if (disk) {
      void diskService.fetchDiskStats(disk);
    }
  },
  { immediate: true }
);
</script>

<template>
  <div class="bg-base-100 rounded-box shadow-sm w-full h-full flex flex-col overflow-hidden">
    <div class="px-3 pt-2 pb-1 border-b border-base-200 shrink-0 bg-base-100 z-10">
      <DiskSelector class-name="w-full" />
    </div>
    <div
      ref="scrollRef"
      class="flex-1 overflow-y-auto"
    >
      <ul class="p-2 text-sm">
        <li>
          <div
            :data-path="rootPath()"
            :class="`flex items-center gap-1 rounded select-none${rootActive ? ' bg-primary/20 text-primary' : ' hover:bg-base-200'}`"
          >
            <button
              type="button"
              class="btn btn-ghost btn-xs p-0 min-h-0 h-5 w-5 shrink-0"
              :aria-label="rootState?.expanded ? t('collapse_root') : t('expand_root')"
              @click="navigationService.loadTreeNode(rootPath())"
            >
              <ChevronDown
                v-if="rootState?.expanded"
                :size="13"
              />
              <ChevronRight
                v-else
                :size="13"
              />
            </button>
            <button
              type="button"
              class="flex items-center gap-1 flex-1 min-w-0 py-1 pr-2 text-left"
              @click="navigationService.navigateTo(rootPath())"
            >
              <FolderOpen
                v-if="rootState?.expanded"
                :size="14"
                class="shrink-0 text-primary"
              />
              <Folder
                v-else
                :size="14"
                class="shrink-0"
              />
              <span class="text-sm font-medium">/</span>
            </button>
          </div>

          <ul v-if="rootState?.expanded && rootState.loaded">
            <li
              v-if="rootState.children.length === 0"
              class="text-base-content/40 text-xs px-4 py-1"
            >
              {{ t('empty') }}
            </li>
            <template v-else>
              <TreeNodeItem
                v-for="dir in rootState.children"
                :key="dir.path"
                :directory="dir"
                :depth="1"
                :node-state="treeStore.nodes[dir.path]"
                :current-path="fileManagerStore.currentPath"
                :nodes="treeStore.nodes"
                @toggle="(path) => navigationService.loadTreeNode(path)"
                @navigate="(path) => navigationService.navigateTo(path)"
              />
            </template>
          </ul>
        </li>
      </ul>
    </div>
    <div
      v-if="configStore.supportsTrash"
      class="px-3 py-1.5 border-t border-base-200 shrink-0 bg-base-100"
    >
      <button
        type="button"
        class="btn btn-ghost btn-sm w-full justify-start gap-2 text-base-content/70 hover:text-base-content text-xs font-normal"
        @click="uiStore.trashModal = ModalState.Opened"
      >
        <Trash2
          :size="15"
          class="text-error"
        />
        <span>{{ t('trash') }}</span>
      </button>
    </div>
    <DiskUsageWidget />
  </div>
</template>
