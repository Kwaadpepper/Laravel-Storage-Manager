<script setup lang="ts">
defineOptions({
  name: 'TreeView',
});
import DiskSelector from '@ts/components/shared/DiskSelector.vue';
import TreeNodeItem from '@ts/components/layout/tree-view/TreeNodeItem.vue';
import { useService } from '@ts/service-container';
import { useFileManagerStore, useTreeStore } from '@ts/stores';
import { rootPath } from '@ts/types';
import { ChevronDown, ChevronRight, Folder, FolderOpen } from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';

const navigationService = useService('navigationService');
const fileManagerStore = useFileManagerStore();
const treeStore = useTreeStore();

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
</script>

<template>
  <div
    ref="scrollRef"
    class="bg-base-100 rounded-box shadow-sm w-full overflow-y-auto h-full"
  >
    <div class="px-3 pt-2 pb-1 border-b border-base-200 sticky top-0 bg-base-100 z-10">
      <DiskSelector class-name="w-full" />
    </div>
    <ul class="p-2 text-sm">
      <li>
        <div
          :data-path="rootPath()"
          :class="`flex items-center gap-1 rounded select-none${rootActive ? ' bg-primary/20 text-primary' : ' hover:bg-base-200'}`"
        >
          <button
            type="button"
            class="btn btn-ghost btn-xs p-0 min-h-0 h-5 w-5 shrink-0"
            :aria-label="rootState?.expanded ? 'Collapse root' : 'Expand root'"
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
            Empty
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
</template>
