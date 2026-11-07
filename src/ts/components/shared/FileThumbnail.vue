<script setup lang="ts">
defineOptions({
  name: 'FileThumbnail',
});

import { computed, ref, watch } from 'vue';
import { TreeNodeFile } from '@ts/types';
import { useDownload } from '@ts/composables';
import FileIcon from '@ts/components/shared/FileIcon.vue';

const props = withDefaults(
  defineProps<{
    item: TreeNodeFile;
    size?: number;
    class?: string;
  }>(),
  {
    size: 48,
    class: '',
  }
);

const { getThumbnailUrl } = useDownload();
const hasError = ref(false);
const isLoaded = ref(false);

const thumbnailUrl = computed(() => getThumbnailUrl(props.item.path));

watch(thumbnailUrl, () => {
  isLoaded.value = false;
  hasError.value = false;
});

function onLoad(): void {
  isLoaded.value = true;
}

function onError(): void {
  hasError.value = true;
}
</script>

<template>
  <FileIcon
    v-if="hasError"
    :name="item.name"
    :extension="item.extension"
    :size="size"
    :class="$props.class"
  />
  <div
    v-else
    class="relative flex items-center justify-center overflow-hidden rounded select-none shrink-0 bg-base-200/40"
    :style="{ width: `${size}px`, height: `${size}px` }"
  >
    <div
      v-if="!isLoaded"
      class="skeleton absolute inset-0 rounded"
    />
    <img
      :src="thumbnailUrl"
      :alt="item.name"
      loading="lazy"
      decoding="async"
      :width="size"
      :height="size"
      class="w-full h-full object-cover transition-opacity duration-200"
      :class="[isLoaded ? 'opacity-100' : 'opacity-0', $props.class]"
      @load="onLoad"
      @error="onError"
    >
  </div>
</template>
