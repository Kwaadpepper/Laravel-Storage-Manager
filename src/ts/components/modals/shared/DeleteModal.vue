<script setup lang="ts">
defineOptions({
  name: 'DeleteModal',
});
import { AlertTriangle, Trash2, X } from 'lucide-vue-next';
import { useEscapeClose } from '@ts/tools';
import { t } from '@ts/i18n';
import { useDeleteViewModel } from './useDeleteViewModel';

const vm = useDeleteViewModel();

function onClose(): void {
  vm.close();
}

useEscapeClose(onClose);
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
        <template v-if="vm.supportsTrash.value && !vm.permanentDelete.value">
          Move {{ vm.itemCount.value === 1 ? 'Item' : 'Items' }} to Trash
        </template>
        <template v-else>
          Permanently Delete {{ vm.itemCount.value === 1 ? 'Item' : 'Items' }}
        </template>
      </h3>

      <div class="py-4">
        <p>
          Are you sure you want to
          <template v-if="vm.supportsTrash.value && !vm.permanentDelete.value">
            move
          </template>
          <template v-else>
            permanently delete
          </template>
          <template v-if="vm.itemCount.value === 1">
            <span class="font-semibold">{{ vm.singleItemName.value }}</span>
          </template>
          <template v-else>
            <span class="font-semibold">{{ vm.itemCount.value }} items</span>
          </template>
          <template v-if="vm.supportsTrash.value && !vm.permanentDelete.value">
            to the trash?
          </template>
          <template v-else>
            ?
          </template>
        </p>

        <p
          v-if="!vm.supportsTrash.value || vm.permanentDelete.value"
          class="text-error text-sm mt-2 flex items-center gap-1.5"
        >
          <AlertTriangle :size="15" />
          <span>This action cannot be undone.</span>
        </p>

        <div
          v-if="vm.supportsTrash.value"
          class="form-control mt-4 pt-2 border-t border-base-content/10"
        >
          <label class="label cursor-pointer justify-start gap-2.5 py-1">
            <input
              v-model="vm.permanentDelete.value"
              type="checkbox"
              class="checkbox checkbox-sm checkbox-error"
            >
            <span class="label-text font-medium text-sm">Delete permanently (bypass trash)</span>
          </label>
        </div>
      </div>

      <div class="flex justify-end gap-2">
        <button
          class="btn"
          type="button"
          @click="onClose"
        >
          {{ t('cancel') }}
        </button>
        <button
          :class="vm.permanentDelete.value || !vm.supportsTrash.value ? 'btn btn-error' : 'btn btn-warning'"
          type="button"
          @click="vm.submit()"
        >
          <Trash2 :size="16" />
          {{ vm.permanentDelete.value || !vm.supportsTrash.value ? 'Delete Permanently' : 'Move to Trash' }}
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
        {{ t('close') }}
      </button>
    </form>
  </dialog>
</template>
