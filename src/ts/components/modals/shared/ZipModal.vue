<script setup lang="ts">
defineOptions({
  name: 'ZipModal',
});

import { useEscapeClose } from '@ts/tools';
import { useZipViewModel } from './useZipViewModel';

const vm = useZipViewModel();
useEscapeClose(vm.close);
</script>

<template>
  <div
    class="modal"
    :class="{ 'modal-open': vm.isOpen.value }"
  >
    <div class="modal-box max-w-md">
      <h3 class="font-bold text-lg mb-2">
        Create ZIP Archive
      </h3>
      <p class="text-sm text-base-content/70 mb-4">
        Compress {{ vm.itemCount.value }} selected item{{ vm.itemCount.value > 1 ? 's' : '' }} into a zip archive.
      </p>

      <div
        v-if="vm.formErrorMessage.value"
        class="alert alert-error text-sm mb-3 py-2"
      >
        <span>{{ vm.formErrorMessage.value }}</span>
      </div>

      <fieldset class="fieldset">
        <legend class="fieldset-legend font-semibold">
          Archive File Name
        </legend>
        <input
          v-model="vm.archiveName.value"
          type="text"
          class="input input-bordered w-full"
          :class="{ 'input-error': vm.archiveNameError.value }"
          placeholder="archive.zip"
        >
        <span
          v-if="vm.archiveNameError.value"
          class="fieldset-label text-error text-xs"
        >
          {{ vm.archiveNameError.value }}
        </span>
      </fieldset>

      <div class="modal-action mt-6">
        <button
          type="button"
          class="btn btn-ghost"
          :disabled="vm.isSubmitting.value"
          @click="vm.close"
        >
          Cancel
        </button>
        <button
          type="button"
          class="btn btn-primary"
          :disabled="vm.isSubmitting.value || vm.itemCount.value === 0"
          @click="vm.submit"
        >
          <span
            v-if="vm.isSubmitting.value"
            class="loading loading-spinner loading-xs"
          />
          Create Archive
        </button>
      </div>
    </div>
    <form
      method="dialog"
      class="modal-backdrop"
    >
      <button
        type="button"
        @click="vm.close"
      >
        close
      </button>
    </form>
  </div>
</template>
