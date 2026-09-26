<script setup lang="ts">
defineOptions({
  name: 'DeleteModal',
});
import { Trash2, X } from 'lucide-vue-next';
import { onMounted, onUnmounted } from 'vue';
import { useDeleteViewModel } from './useDeleteViewModel';

const vm = useDeleteViewModel();

function onClose(): void {
  vm.close();
}

onMounted(() => {
  const handleKeydown = (e: KeyboardEvent): void => {
    if (e.key === 'Escape') {
      onClose();
    }
  };
  window.addEventListener('keydown', handleKeydown);
  onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
  });
});
</script>

<template>
  <dialog
    class="modal"
    :class="{ 'modal-open': vm.isOpen.value }"
    @close="onClose"
  >
    <div class="modal-box">
      <button
        class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2"
        type="button"
        @click="onClose"
      >
        <X :size="16" />
      </button>
      <h3 class="font-bold text-lg">
        Delete {{ vm.itemCount.value === 1 ? 'Item' : 'Items' }}
      </h3>

      <div class="py-4">
        <p>
          Are you sure you want to delete
          <template v-if="vm.itemCount.value === 1">
            <span class="font-semibold">{{ vm.singleItemName.value }}</span>
          </template>
          <template v-else>
            <span class="font-semibold">{{ vm.itemCount.value }} items</span>
          </template>?
        </p>
      </div>

      <div class="flex justify-center gap-2">
        <button
          class="btn"
          type="button"
          @click="onClose"
        >
          Cancel
        </button>
        <button
          class="btn btn-error"
          type="button"
          @click="vm.submit()"
        >
          <Trash2 :size="16" />
          Delete
        </button>
      </div>
    </div>

    <form
      method="dialog"
      class="modal-backdrop"
    >
      <button
        type="button"
        @click="onClose"
      >
        Close
      </button>
    </form>
  </dialog>
</template>
