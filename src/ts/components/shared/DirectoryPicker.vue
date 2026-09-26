<script setup lang="ts">
defineOptions({
  name: 'DirectoryPicker',
});
import DiskSelector from '@ts/components/shared/DiskSelector.vue';
import TreeNodeItem from '@ts/components/layout/tree-view/TreeNodeItem.vue';
import { useService } from '@ts/service-container';
import { useTreeStore } from '@ts/stores';
import { Path, rootPath } from '@ts/types';
import { ChevronDown, ChevronRight, Folder, FolderOpen } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
  selectedPath: Path | null;
}>();

const emit = defineEmits<{
  (e: 'select', path: Path): void;
}>();

const navigationService = useService('navigationService');
const treeStore = useTreeStore();

const rootState = computed(() => treeStore.nodes[rootPath()]);
const rootActive = computed(() => props.selectedPath === rootPath());
</script>

<template>
  <div class="bg-base-200/50 rounded-box border border-base-200 shadow-inner w-full overflow-y-auto h-[400px]">
    <div class="px-3 pt-2 pb-1 border-b border-base-200 sticky top-0 bg-base-100 z-10 shadow-sm">
      <DiskSelector class-name="w-full" />
    </div>
    <ul class="p-2 text-sm">
      <li>
        <div
          :data-path="rootPath()"
          :class="`flex items-center gap-1 rounded select-none${rootActive ? ' bg-primary text-primary-content' : ' hover:bg-base-300'}`"
        >
          <button
            type="button"
            class="btn btn-ghost btn-xs p-0 min-h-0 h-5 w-5 shrink-0"
            :aria-label="rootState?.expanded ? 'Collapse root' : 'Expand root'"
            @click.stop="navigationService.loadTreeNode(rootPath())"
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
            @click="emit('select', rootPath())"
          >
            <FolderOpen
              v-if="rootState?.expanded"
              :size="14"
              class="shrink-0"
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
              :current-path="selectedPath ?? ('' as Path)"
              :nodes="treeStore.nodes"
              @toggle="(path) => navigationService.loadTreeNode(path)"
              @navigate="(path) => emit('select', path)"
            />
          </template>
        </ul>
      </li>
    </ul>
  </div>
</template>
