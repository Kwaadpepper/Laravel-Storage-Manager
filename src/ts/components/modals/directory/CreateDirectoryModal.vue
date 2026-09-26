<script setup lang="ts">
defineOptions({
  name: 'CreateDirectoryModal',
});
import { nextTick, onMounted, onUnmounted, ref } from 'vue';
import { FolderPlus, X } from 'lucide-vue-next';
import FieldError from '@ts/components/shared/FieldError.vue';
import {
  directoryNameMaxLength,
  directoryNameMinLength,
  useCreateDirectoryViewModel,
} from './useCreateDirectoryViewModel';

const vm = useCreateDirectoryViewModel();

const directoryNameInputRef = ref<HTMLInputElement | null>(null);

function onClose(): void {
  vm.close();
}

onMounted(async () => {
  await nextTick();
  directoryNameInputRef.value?.focus();

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

const onFormSubmit = async (): Promise<void> => {
  await vm.submit();
};
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
        Create New Directory
      </h3>

      <p
        v-if="vm.formErrorMessage.value"
        class="text-error text-center mt-2"
      >
        {{ vm.formErrorMessage.value }}
      </p>

      <form
        class="fieldset flex flex-col justify-center gap-4 py-4"
        @submit.prevent="onFormSubmit"
      >
        <label class="input validator mx-auto">
          <FolderPlus class="h-[1em] opacity-50" />
          <input
            ref="directoryNameInputRef"
            v-model="vm.directoryName.value"
            class="grow"
            type="text"
            placeholder="My New Directory"
            autofocus
            autocomplete="off"
            :minlength="directoryNameMinLength"
            :maxlength="directoryNameMaxLength"
            required
            :aria-invalid="vm.directoryNameFieldError.value ? 'true' : 'false'"
          >
        </label>
        <FieldError :field-error="vm.directoryNameFieldError.value ?? undefined" />

        <label class="input mx-auto">
          <span>Into</span>
          <input
            class="grow"
            type="text"
            :placeholder="vm.currentPath.value"
            readonly
            disabled
          >
        </label>

        <span class="flex justify-center">
          <button
            class="btn btn-primary"
            type="submit"
            :disabled="vm.isSubmitting.value"
          >
            <span
              v-if="vm.isSubmitting.value"
              class="loading loading-spinner loading-xs"
            />
            Create
          </button>
        </span>
      </form>
    </div>

    <!-- Form to control the modal -->
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
