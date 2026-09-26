<script setup lang="ts">
defineOptions({
  name: 'ViewUnsupported',
});
import { FileX } from 'lucide-vue-next';

defineProps<{
  extension: string | null;
}>();
</script>

<template>
  <div class="flex flex-col items-center justify-center h-full gap-3 text-base-content/50">
    <FileX :size="48" />
    <p class="text-lg font-medium">
      Preview not available
    </p>
    <p
      v-if="extension"
      class="text-sm"
    >
      <span class="badge badge-ghost">.{{ extension }}</span> files cannot be previewed.
    </p>
  </div>
</template>
