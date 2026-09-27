<script setup lang="ts">
defineOptions({
  name: 'ViewImage',
});
import { computed, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps<{
  blob?: Blob | null;
  streamUrl?: string | null;
  fileName: string;
}>();

const objectUrl = ref<string | null>(null);

watch(
  () => props.blob,
  (newBlob) => {
    if (objectUrl.value) {
      URL.revokeObjectURL(objectUrl.value);
    }
    if (newBlob) {
      objectUrl.value = URL.createObjectURL(newBlob);
    } else {
      objectUrl.value = null;
    }
  },
  { immediate: true }
);

const imageSrc = computed(() => props.streamUrl ?? objectUrl.value);

onBeforeUnmount(() => {
  if (objectUrl.value) {
    URL.revokeObjectURL(objectUrl.value);
  }
});
</script>

<template>
  <div
    v-if="imageSrc"
    class="flex items-center justify-center h-full overflow-auto"
  >
    <img
      :src="imageSrc"
      :alt="fileName"
      class="max-w-full max-h-full object-contain"
    >
  </div>
</template>
