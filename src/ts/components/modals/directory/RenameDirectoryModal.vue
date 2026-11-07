<script setup lang="ts">
defineOptions({
  name: 'RenameDirectoryModal',
});
import { nextTick, onMounted, ref } from 'vue';
import { Pencil, X } from 'lucide-vue-next';
import FieldError from '@ts/components/shared/FieldError.vue';
import { useEscapeClose } from '@ts/tools';
import { t } from '@ts/i18n';
import {
  directoryNameMaxLength,
  directoryNameMinLength,
  useRenameDirectoryViewModel,
} from './useRenameDirectoryViewModel';

const vm = useRenameDirectoryViewModel();

const directoryNameInputRef = ref<HTMLInputElement | null>(null);

function onClose(): void {
  vm.close();
}

useEscapeClose(onClose);

onMounted(async () => {
  await nextTick();
  directoryNameInputRef.value?.focus();
  directoryNameInputRef.value?.select();
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
        {{ t('rename_directory_title') }}
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
          <Pencil class="h-[1em] opacity-50" />
          <input
            ref="directoryNameInputRef"
            v-model="vm.directoryName.value"
            class="grow"
            type="text"
            :placeholder="vm.targetName.value"
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
          <span>{{ t('rename') }}</span>
          <input
            class="grow"
            type="text"
            :value="vm.targetDirectoryPath.value ?? ''"
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
            {{ t('rename') }}
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
        {{ t('close') }}
      </button>
    </form>
  </dialog>
</template>
