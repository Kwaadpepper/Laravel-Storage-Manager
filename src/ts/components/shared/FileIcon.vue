<script setup lang="ts">
defineOptions({
  name: 'FileIcon',
});

import { getFileTypeInfo } from '@ts/utils/file-type';
import { computed } from 'vue';

const props = withDefaults(
  defineProps<{
    name?: string;
    extension?: string | null;
    size?: number | string;
    strokeWidth?: number | string;
    customColor?: boolean;
    class?: string;
  }>(),
  {
    name: '',
    extension: null,
    size: 16,
    strokeWidth: 2,
    customColor: true,
    class: '',
  }
);

const fileTypeInfo = computed(() => getFileTypeInfo(props.name, props.extension));
</script>

<template>
  <component
    :is="fileTypeInfo.component"
    :size="size"
    :stroke-width="strokeWidth"
    :class="[customColor ? fileTypeInfo.colorClass : '', $props.class]"
  />
</template>
