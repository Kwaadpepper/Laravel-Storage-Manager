<script setup lang="ts">
defineOptions({
  name: 'FileSize',
});
import { computed } from 'vue';

const props = defineProps<{
  bytes: number;
}>();

function formatBytes(bytes: number): string {
  if (bytes === 0) return '0 o';

  const units = ['o', 'Ko', 'Mo', 'Go', 'To'];
  const i = Math.min(Math.floor(Math.log2(bytes) / 10), units.length - 1);

  if (i === 0) return `${bytes} o`;

  const value = bytes / Math.pow(1024, i);
  return `${value.toFixed(2).replace(/\.?0+$/, '')} ${units[i]}`;
}

const formatted = computed(() => formatBytes(props.bytes));
</script>

<template>
  <span>{{ formatted }}</span>
</template>
