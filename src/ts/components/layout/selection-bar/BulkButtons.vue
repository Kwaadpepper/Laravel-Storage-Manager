<script setup lang="ts">
defineOptions({
  name: 'BulkButtons',
});
import CommonButtons from '@ts/components/layout/selection-bar/CommonButtons.vue';
import { TreeNode } from '@ts/types';

defineProps<{
  selectedNodes: TreeNode[];
}>();
</script>

<template>
  <CommonButtons :selected-nodes="selectedNodes" />
</template>
