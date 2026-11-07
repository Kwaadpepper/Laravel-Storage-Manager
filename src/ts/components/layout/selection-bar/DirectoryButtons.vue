<script setup lang="ts">
defineOptions({
  name: 'DirectoryButtons',
});
import { useNavigation } from '@ts/composables';
import { isDirectory, TreeNode } from '@ts/types';
import { t } from '@ts/i18n';
import { FolderOpen } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
  selectedNodes: TreeNode[];
}>();

const navigation = useNavigation();

const hasMultipleSelection = computed(() => props.selectedNodes.length > 1);
const firstSelectedNode = computed<TreeNode | null>(
  () => props.selectedNodes[0] || null
);
const firstNodeIsDir = computed(
  () => firstSelectedNode.value !== null && isDirectory(firstSelectedNode.value)
);

function onClickOpen(): void {
  if (firstSelectedNode.value === null || !firstNodeIsDir.value) {
    return;
  }
  navigation.navigateTo(firstSelectedNode.value.path);
}
</script>

<template>
  <button
    v-if="!hasMultipleSelection && firstNodeIsDir"
    type="button"
    class="btn btn-ghost btn-xs"
    :title="t('open')"
    @click="onClickOpen"
  >
    <FolderOpen :size="14" />
    <span class="hidden sm:inline">{{ t('open') }}</span>
  </button>
</template>
