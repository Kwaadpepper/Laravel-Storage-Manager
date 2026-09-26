<script setup lang="ts">
defineOptions({
  name: 'ViewImage',
});
import { onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps<{
  blob: Blob;
  fileName: string;
}>();

const objectUrl = ref<string | null>(null);

watch(
  () => props.blob,
  (newBlob) => {
    if (objectUrl.value) {
      URL.revokeObjectURL(objectUrl.value);
    }
    objectUrl.value = URL.createObjectURL(newBlob);
  },
  { immediate: true }
);

onBeforeUnmount(() => {
  if (objectUrl.value) {
    URL.revokeObjectURL(objectUrl.value);
  }
});
</script>

<template>
  <div
    v-if="objectUrl"
    class="flex items-center justify-center h-full overflow-auto"
  >
    <img
      :src="objectUrl"
      :alt="fileName"
      class="max-w-full max-h-full object-contain"
    >
  </div>
</template>
