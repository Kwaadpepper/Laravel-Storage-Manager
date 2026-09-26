<script setup lang="ts">
defineOptions({
  name: 'RenameFileModal',
});
import { nextTick, onMounted, onUnmounted, ref } from 'vue';
import { Pencil, X } from 'lucide-vue-next';
import FieldError from '@ts/components/shared/FieldError.vue';
import {
  baseNameMaxLength,
  baseNameMinLength,
  extensionMaxLength,
  useRenameFileViewModel,
} from './useRenameFileViewModel';

const vm = useRenameFileViewModel();

const baseNameInputRef = ref<HTMLInputElement | null>(null);

function onClose(): void {
  vm.close();
}

onMounted(async () => {
  await nextTick();
  baseNameInputRef.value?.focus();
  baseNameInputRef.value?.select();

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
        Rename File
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
        <div class="flex items-start gap-1 mx-auto">
          <div class="flex flex-col gap-1">
            <label class="input validator">
              <Pencil class="h-[1em] opacity-50" />
              <input
                ref="baseNameInputRef"
                v-model="vm.baseName.value"
                class="grow"
                type="text"
                :placeholder="vm.targetBaseName.value"
                autofocus
                autocomplete="off"
                :minlength="baseNameMinLength"
                :maxlength="baseNameMaxLength"
                required
                :aria-invalid="vm.baseNameFieldError.value ? 'true' : 'false'"
              >
            </label>
            <FieldError :field-error="vm.baseNameFieldError.value ?? undefined" />
          </div>

          <span class="self-center px-1 text-base-content/60 select-none">.</span>

          <div class="flex flex-col gap-1">
            <label class="input">
              <input
                v-model="vm.extension.value"
                class="grow w-24"
                type="text"
                :placeholder="vm.targetExtension.value || 'ext'"
                autocomplete="off"
                :maxlength="extensionMaxLength"
                aria-label="Extension"
                :aria-invalid="vm.extensionFieldError.value ? 'true' : 'false'"
              >
            </label>
            <FieldError :field-error="vm.extensionFieldError.value ?? undefined" />
          </div>
        </div>

        <label class="input mx-auto">
          <span>Rename</span>
          <input
            class="grow"
            type="text"
            :value="vm.targetFilePath.value ?? ''"
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
            Rename
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
