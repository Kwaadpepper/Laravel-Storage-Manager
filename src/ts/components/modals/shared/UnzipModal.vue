<script setup lang="ts">
defineOptions({
  name: 'UnzipModal',
});

import { useEscapeClose } from '@ts/tools';
import { useUnzipViewModel } from './useUnzipViewModel';

const vm = useUnzipViewModel();
useEscapeClose(vm.close);
</script>

<template>
  <div
    class="modal"
    :class="{ 'modal-open': vm.isOpen.value }"
  >
    <div class="modal-box max-w-md">
      <h3 class="font-bold text-lg mb-2">
        Extract ZIP Archive
      </h3>
      <p class="text-sm text-base-content/70 mb-4">
        Extract <span class="font-mono font-semibold">{{ vm.archiveName.value }}</span>
      </p>

      <div
        v-if="vm.formErrorMessage.value"
        class="alert alert-error text-sm mb-3 py-2"
      >
        <span>{{ vm.formErrorMessage.value }}</span>
      </div>

      <div class="flex flex-col gap-3">
        <label class="flex items-center gap-2 cursor-pointer">
          <input
            v-model="vm.extractMode.value"
            type="radio"
            value="subfolder"
            class="radio radio-primary radio-sm"
          >
          <span class="text-sm font-medium">Extract to new folder:</span>
        </label>

        <div
          v-if="vm.extractMode.value === 'subfolder'"
          class="pl-6"
        >
          <input
            v-model="vm.subfolderName.value"
            type="text"
            class="input input-sm input-bordered w-full"
            placeholder="folder_name"
          >
        </div>

        <label class="flex items-center gap-2 cursor-pointer mt-1">
          <input
            v-model="vm.extractMode.value"
            type="radio"
            value="current"
            class="radio radio-primary radio-sm"
          >
          <span class="text-sm font-medium">Extract here (in current directory)</span>
        </label>
      </div>

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
          :disabled="vm.isSubmitting.value || !vm.archivePath.value"
          @click="vm.submit"
        >
          <span
            v-if="vm.isSubmitting.value"
            class="loading loading-spinner loading-xs"
          />
          Extract Archive
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
