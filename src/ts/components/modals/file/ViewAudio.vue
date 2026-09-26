<script setup lang="ts">
defineOptions({
  name: 'ViewAudio',
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
    class="flex flex-col items-center justify-center h-full gap-4"
  >
    <p class="text-base-content/60 text-sm">
      {{ fileName }}
    </p>
    <audio
      controls
      :src="objectUrl"
      class="w-full max-w-md"
    />
  </div>
</template>
