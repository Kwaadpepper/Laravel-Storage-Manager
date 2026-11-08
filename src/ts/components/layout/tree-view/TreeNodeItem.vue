<script setup lang="ts">
import { TreeNodeState } from '@ts/stores';
import { Path, TreeNodeDirectory } from '@ts/types';
import { ChevronDown, ChevronRight, Folder, FolderOpen } from 'lucide-vue-next';
import { computed } from 'vue';

defineOptions({
  name: 'TreeNodeItem',
});

const props = defineProps<{
  directory: TreeNodeDirectory;
  depth: number;
  nodeState: TreeNodeState | undefined;
  currentPath: Path;
  nodes: Record<string, TreeNodeState>;
}>();

const emit = defineEmits<{
  (e: 'toggle', path: Path): void;
  (e: 'navigate', path: Path): void;
}>();

const isExpanded = computed(() => props.nodeState?.expanded ?? false);
const isActive = computed(() => props.currentPath === props.directory.path);
const hasChildren = computed(() => {
  if (props.nodeState?.loaded === true) {
    return props.nodeState.children.length > 0;
  }
  return props.directory.hasSubDirectories;
});
</script>

<template>
  <li>
    <div
      :data-path="directory.path"
      :class="`flex items-center gap-1 rounded select-none${isActive ? ' bg-primary/20 text-primary' : ' hover:bg-base-200'}`"
      :style="{ paddingLeft: `${depth * 14}px` }"
    >
      <button
        type="button"
        :class="`btn btn-ghost btn-xs p-0 min-h-0 h-5 w-5 me-3 shrink-0${hasChildren ? '' : ' invisible pointer-events-none'}`"
        :tabindex="hasChildren ? 0 : -1"
        :aria-label="isExpanded ? 'Collapse' : 'Expand'"
        @click.stop="emit('toggle', directory.path)"
      >
        <ChevronDown
          v-if="isExpanded"
          :size="13"
        />
        <ChevronRight
          v-else
          :size="13"
        />
      </button>
      <button
        type="button"
        class="flex items-center gap-1 flex-1 min-w-0 py-2 pr-2 cursor-pointer text-left overflow-hidden"
        :title="directory.name"
        @click="emit('navigate', directory.path)"
      >
        <FolderOpen
          v-if="isExpanded"
          :size="14"
          class="shrink-0 text-primary"
        />
        <Folder
          v-else
          :size="14"
          class="shrink-0"
        />
        <span class="truncate text-sm">{{ directory.name }}</span>
      </button>
    </div>

    <ul v-if="isExpanded && nodeState?.loaded && nodeState.children.length > 0">
      <TreeNodeItem
        v-for="child in nodeState.children"
        :key="child.path"
        :directory="child"
        :depth="depth + 1"
        :node-state="nodes[child.path]"
        :current-path="currentPath"
        :nodes="nodes"
        @toggle="(path) => emit('toggle', path)"
        @navigate="(path) => emit('navigate', path)"
      />
    </ul>
  </li>
</template>
