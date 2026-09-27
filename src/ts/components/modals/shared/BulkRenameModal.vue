<script setup lang="ts">
defineOptions({
  name: 'BulkRenameModal',
});

import { useEscapeClose } from '@ts/tools';
import { useBulkRenameViewModel } from './useBulkRenameViewModel';

const vm = useBulkRenameViewModel();
useEscapeClose(vm.close);
</script>

<template>
  <div
    class="modal"
    :class="{ 'modal-open': vm.isOpen.value }"
  >
    <div class="modal-box max-w-2xl flex flex-col max-h-[90vh]">
      <h3 class="font-bold text-lg mb-2">
        Bulk Rename ({{ vm.itemCount.value }} items)
      </h3>

      <div
        v-if="vm.formErrorMessage.value"
        class="alert alert-error text-sm mb-3 py-2"
      >
        <span>{{ vm.formErrorMessage.value }}</span>
      </div>

      <div class="flex flex-col gap-4 overflow-y-auto pr-1">
        <!-- Pattern input -->
        <fieldset class="fieldset">
          <legend class="fieldset-legend font-semibold">
            Rename Pattern
          </legend>
          <input
            v-model="vm.pattern.value"
            type="text"
            class="input input-bordered w-full"
            placeholder="e.g. photo_{n}"
          >
          <div class="flex flex-wrap gap-1.5 mt-2 text-xs">
            <span class="text-base-content/60 self-center mr-1">Insert:</span>
            <button
              type="button"
              class="badge badge-outline cursor-pointer hover:badge-primary"
              @click="vm.setPatternToken('{n}')"
            >
              {n} (Counter)
            </button>
            <button
              type="button"
              class="badge badge-outline cursor-pointer hover:badge-primary"
              @click="vm.setPatternToken('{name}')"
            >
              {name} (Original)
            </button>
            <button
              type="button"
              class="badge badge-outline cursor-pointer hover:badge-primary"
              @click="vm.setPatternToken('{date}')"
            >
              {date} (YYYY-MM-DD)
            </button>
            <button
              type="button"
              class="badge badge-outline cursor-pointer hover:badge-primary"
              @click="vm.setPatternToken('{ext}')"
            >
              {ext} (Extension)
            </button>
          </div>
        </fieldset>

        <!-- Options row -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <fieldset class="fieldset">
            <legend class="fieldset-legend text-xs">
              Start Number
            </legend>
            <input
              v-model.number="vm.startNumber.value"
              type="number"
              min="0"
              class="input input-sm input-bordered w-full"
            >
          </fieldset>

          <fieldset class="fieldset">
            <legend class="fieldset-legend text-xs">
              Padding (Digits)
            </legend>
            <select
              v-model.number="vm.padding.value"
              class="select select-sm select-bordered w-full"
            >
              <option :value="1">
                1 (1, 2...)
              </option>
              <option :value="2">
                2 (01, 02...)
              </option>
              <option :value="3">
                3 (001, 002...)
              </option>
              <option :value="4">
                4 (0001...)
              </option>
            </select>
          </fieldset>

          <fieldset class="fieldset flex items-end pb-1">
            <label class="fieldset-label flex items-center gap-2 cursor-pointer">
              <input
                v-model="vm.preserveExtension.value"
                type="checkbox"
                class="checkbox checkbox-sm checkbox-primary"
              >
              <span class="text-xs">Preserve file extension</span>
            </label>
          </fieldset>
        </div>

        <!-- Duplicate warning -->
        <div
          v-if="vm.hasDuplicates.value"
          class="alert alert-warning text-xs py-2"
        >
          <span>Collision detected! Multiple files will share the same name.</span>
        </div>

        <!-- Live Preview Table -->
        <fieldset class="fieldset">
          <legend class="fieldset-legend font-semibold">
            Preview
          </legend>
          <div class="border border-base-300 rounded-lg overflow-hidden max-h-48 overflow-y-auto">
            <table class="table table-xs table-pin-rows">
              <thead>
                <tr>
                  <th>Original Name</th>
                  <th>New Name</th>
                  <th class="w-16">
                    Status
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(item, idx) in vm.previewItems.value"
                  :key="idx"
                  :class="{ 'bg-error/10': item.hasConflict }"
                >
                  <td
                    class="font-mono text-xs truncate max-w-[180px]"
                    :title="item.originalName"
                  >
                    {{ item.originalName }}
                  </td>
                  <td
                    class="font-mono text-xs truncate max-w-[200px]"
                    :title="item.newName"
                  >
                    {{ item.newName }}
                  </td>
                  <td>
                    <span
                      v-if="item.hasConflict"
                      class="badge badge-error badge-xs"
                    >Conflict</span>
                    <span
                      v-else
                      class="badge badge-success badge-xs"
                    >OK</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </fieldset>
      </div>

      <div class="modal-action mt-4">
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
          :disabled="!vm.canSubmit.value || vm.isSubmitting.value"
          @click="vm.submit"
        >
          <span
            v-if="vm.isSubmitting.value"
            class="loading loading-spinner loading-xs"
          />
          Rename {{ vm.itemCount.value }} items
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
