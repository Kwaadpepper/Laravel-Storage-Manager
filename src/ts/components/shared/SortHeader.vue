<script setup lang="ts">
defineOptions({
  name: 'SortHeader',
});
import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-vue-next';

defineProps<{
  label: string;
  direction: 'asc' | 'desc' | null;
}>();

const emit = defineEmits<{
  (e: 'click'): void;
}>();
</script>

<template>
  <div
    class="table-cell p-2 sticky top-0 bg-base-100 z-10 border-b border-base-200 cursor-pointer select-none"
    @click="emit('click')"
  >
    <div class="flex items-center gap-1">
      {{ label }}
      <ArrowUp
        v-if="direction === 'asc'"
        :size="14"
      />
      <ArrowDown
        v-else-if="direction === 'desc'"
        :size="14"
      />
      <ArrowUpDown
        v-else
        :size="14"
        class="opacity-40"
      />
    </div>
  </div>
</template>
