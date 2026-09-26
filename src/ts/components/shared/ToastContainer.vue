<script setup lang="ts">
defineOptions({
  name: 'ToastContainer',
});
import { useToastStore } from '@ts/stores';
import { X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
  top?: boolean;
  bottom?: boolean;
  left?: boolean;
  right?: boolean;
}>();

const toastStore = useToastStore();
const containerRef = ref<HTMLDivElement | null>(null);

watch(
  () => toastStore.toasts.length,
  (len) => {
    if (len > 0) {
      containerRef.value?.showPopover?.();
    }
  }
);

const positionClasses = computed(() =>
  [
    props.top ? 'toast-top' : '',
    props.bottom ? 'toast-bottom' : '',
    props.left ? 'toast-start' : '',
    props.right ? 'toast-end' : '',
  ]
    .filter(Boolean)
    .join(' ')
);
</script>

<template>
  <div
    ref="containerRef"
    popover="manual"
    :class="`toast ${positionClasses} pointer-events-none border-none bg-transparent p-0 overflow-visible`"
  >
    <div
      v-for="toast in toastStore.toasts"
      :key="toast.id"
      class="alert pointer-events-auto"
      :class="{
        'alert-success': toast.type === 'success',
        'alert-error': toast.type === 'error',
        'alert-info': toast.type === 'info',
        'alert-warning': toast.type === 'warning',
      }"
    >
      <button
        v-if="!toast.persistent"
        class="btn btn-sm btn-ghost"
        title="Close"
        @click="toastStore.pullToast(toast.id)"
      >
        <X :size="16" />
      </button>
      <span
        v-if="toast.title"
        class="font-bold"
      >{{ toast.title }}</span>
      <span>{{ toast.message }}</span>
    </div>
  </div>
</template>
