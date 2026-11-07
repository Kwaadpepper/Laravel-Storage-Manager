<script setup lang="ts">
defineOptions({
  name: 'SelectionBar',
});
import ClipboardButtons from '@ts/components/layout/selection-bar/ClipboardButtons.vue';
import CommonButtons from '@ts/components/layout/selection-bar/CommonButtons.vue';
import DirectoryButtons from '@ts/components/layout/selection-bar/DirectoryButtons.vue';
import FileButtons from '@ts/components/layout/selection-bar/FileButtons.vue';
import { useFileManagerStore } from '@ts/stores';
import { t } from '@ts/i18n';
import { X } from 'lucide-vue-next';
import { computed } from 'vue';

const fileManagerStore = useFileManagerStore();

const selectedNodesList = computed(() => Object.values(fileManagerStore.selectedNodes));

const selectedNodesLabel = computed(() =>
  selectedNodesList.value.reduce(
    (label, node) => (label.length ? t('multiple_selections') : node.name),
    ''
  )
);

function onClickDeselect(): void {
  fileManagerStore.selectNodes();
}
</script>

<template>
  <div
    class="flex items-center gap-2 px-4 h-9 border-b transition-colors bg-primary/10 border-primary/20"
  >
    <span
      v-if="selectedNodesLabel.length"
      class="text-sm font-medium text-base-content/70 truncate max-w-xs"
      :title="selectedNodesLabel"
    >
      {{ selectedNodesLabel }}
    </span>
    <span
      v-else
      class="text-md font-bold italic text-base-content/70 truncate max-w-xs"
      :title="fileManagerStore.currentBaseName"
    >
      {{ fileManagerStore.currentBaseName }}
    </span>

    <div class="flex items-center gap-1 ml-2">
      <DirectoryButtons :selected-nodes="selectedNodesList" />
      <FileButtons :selected-nodes="selectedNodesList" />
    </div>

    <!-- CLIPBOARD ACTIONS -->
    <div class="flex items-center gap-1 ml-2">
      <ClipboardButtons :selected-nodes="selectedNodesList" />
    </div>

    <!-- COMMON ACTIONS -->
    <div class="flex items-center gap-1 ml-2">
      <CommonButtons :selected-nodes="selectedNodesList" />
    </div>

    <button
      v-if="selectedNodesList.length > 0"
      type="button"
      class="btn btn-ghost btn-xs ml-auto"
      :title="t('deselect')"
      @click="onClickDeselect"
    >
      <X :size="14" />
    </button>
  </div>
</template>
