<script setup lang="ts">
defineOptions({
  name: 'TrashModal',
});

import { Str, useEscapeClose } from '@ts/tools';
import {
  AlertTriangle,
  File,
  Folder,
  Loader,
  Loader2,
  RefreshCw,
  RotateCcw,
  Search,
  Trash2,
  X,
} from 'lucide-vue-next';
import { useTrashViewModel } from './useTrashViewModel';

const vm = useTrashViewModel();

function onClose(): void {
  vm.close();
}

function formatDate(dateStr: string): string {
  const d = new Date(dateStr);
  return Number.isNaN(d.getTime()) ? dateStr : d.toLocaleString();
}

useEscapeClose(onClose);
</script>

<template>
  <dialog
    class="modal"
    :class="{ 'modal-open': vm.isOpen.value }"
    @close="onClose"
  >
    <div class="modal-box flex flex-col p-0 max-h-[85vh] w-11/12 max-w-4xl overflow-hidden">
      <!-- Modal Header -->
      <div class="flex items-center justify-between p-4 border-b border-base-content/10 shrink-0">
        <h3 class="font-bold text-base flex items-center gap-2">
          <Trash2
            :size="18"
            class="text-error"
          />
          <span>Trash</span>
          <span
            v-if="vm.currentDisk.value"
            class="badge badge-sm badge-neutral"
          >
            {{ vm.currentDisk.value }}
          </span>
          <span class="text-xs text-base-content/50 font-normal">
            ({{ vm.items.value.length }} {{ vm.items.value.length === 1 ? 'item' : 'items' }})
          </span>
        </h3>
        <button
          type="button"
          class="btn btn-sm btn-circle btn-ghost"
          aria-label="Close"
          @click="onClose"
        >
          <X :size="16" />
        </button>
      </div>

      <!-- Action Toolbar -->
      <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5 border-b border-base-content/10 bg-base-200/40 shrink-0">
        <!-- Search Input -->
        <div class="relative flex-1 min-w-[180px] max-w-xs">
          <Search
            :size="14"
            class="absolute left-2.5 top-1/2 -translate-y-1/2 text-base-content/40 pointer-events-none"
          />
          <input
            v-model="vm.searchQuery.value"
            type="text"
            placeholder="Filter trash..."
            class="input input-sm input-bordered w-full pl-8 pr-7 text-xs"
          >
          <button
            v-if="vm.searchQuery.value"
            type="button"
            class="btn btn-xs btn-ghost btn-circle absolute right-1 top-1/2 -translate-y-1/2 text-base-content/50"
            @click="vm.searchQuery.value = ''"
          >
            <X :size="12" />
          </button>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-2">
          <template v-if="vm.selectedIds.value.length > 0">
            <button
              type="button"
              class="btn btn-sm btn-success btn-outline gap-1"
              :disabled="!vm.canRestoreSelected.value"
              @click="vm.restoreItems(vm.selectedIds.value)"
            >
              <RotateCcw :size="14" />
              <span>Restore ({{ vm.nonProcessingSelectedCount.value }})</span>
            </button>
            <button
              type="button"
              class="btn btn-sm btn-error btn-outline gap-1"
              :disabled="!vm.canPurgeSelected.value"
              @click="vm.purgeItems(vm.selectedIds.value)"
            >
              <Trash2 :size="14" />
              <span>Delete ({{ vm.nonProcessingSelectedCount.value }})</span>
            </button>
          </template>

          <button
            type="button"
            class="btn btn-sm btn-ghost gap-1"
            title="Refresh trash"
            :disabled="vm.isLoading.value"
            @click="vm.fetchTrash()"
          >
            <RefreshCw
              :size="14"
              :class="{ 'animate-spin': vm.isLoading.value }"
            />
          </button>

          <button
            type="button"
            class="btn btn-sm btn-error gap-1"
            :disabled="vm.items.value.length === 0 || vm.isAnyProcessing.value"
            :title="vm.isAnyProcessing.value ? 'Cannot empty trash while operations are in progress' : 'Empty Trash'"
            @click="vm.confirmEmptyOpen.value = true"
          >
            <Loader2
              v-if="vm.isEmptying.value"
              :size="14"
              class="animate-spin"
            />
            <Trash2
              v-else
              :size="14"
            />
            <span>Empty Trash</span>
          </button>
        </div>
      </div>

      <!-- Confirm Empty Banner -->
      <div
        v-if="vm.confirmEmptyOpen.value"
        class="bg-error/10 border-b border-error/20 p-3 flex items-center justify-between gap-3 shrink-0"
      >
        <div class="flex items-center gap-2 text-sm text-error">
          <AlertTriangle
            :size="16"
            class="shrink-0"
          />
          <span>Are you sure you want to permanently delete all items in trash? This cannot be undone.</span>
        </div>
        <div class="flex items-center gap-2 shrink-0">
          <button
            type="button"
            class="btn btn-xs"
            :disabled="vm.isEmptying.value"
            @click="vm.confirmEmptyOpen.value = false"
          >
            Cancel
          </button>
          <button
            type="button"
            class="btn btn-xs btn-error"
            :disabled="vm.isEmptying.value || vm.isAnyProcessing.value"
            @click="vm.emptyTrash()"
          >
            Yes, Empty Trash
          </button>
        </div>
      </div>

      <!-- Main Content / Table -->
      <div class="flex-1 overflow-y-auto min-h-[250px]">
        <!-- Loading State -->
        <div
          v-if="vm.isLoading.value"
          class="flex flex-col items-center justify-center h-full min-h-[250px] gap-2 text-base-content/50"
        >
          <Loader
            :size="24"
            class="animate-spin text-primary"
          />
          <span class="text-sm">Loading trash...</span>
        </div>

        <!-- Empty State -->
        <div
          v-else-if="vm.items.value.length === 0"
          class="flex flex-col items-center justify-center h-full min-h-[250px] gap-3 text-base-content/40 p-6"
        >
          <Trash2 :size="48" />
          <div class="text-center">
            <p class="font-medium text-base text-base-content/70">
              Trash is empty
            </p>
            <p class="text-xs mt-1">
              Items you delete will be kept here until the retention period expires or trash is emptied.
            </p>
          </div>
        </div>

        <!-- Empty Search State -->
        <div
          v-else-if="vm.filteredItems.value.length === 0"
          class="flex flex-col items-center justify-center h-full min-h-[250px] gap-2 text-base-content/40 p-6"
        >
          <Search :size="32" />
          <p class="text-sm">
            No items match "{{ vm.searchQuery.value }}"
          </p>
        </div>

        <!-- Table -->
        <table
          v-else
          class="table table-sm table-pin-rows w-full"
        >
          <thead>
            <tr class="bg-base-200/80 text-xs">
              <th class="w-10 text-center">
                <input
                  type="checkbox"
                  class="checkbox checkbox-xs"
                  :checked="vm.isAllSelected.value"
                  :indeterminate="vm.isIndeterminate.value"
                  :disabled="vm.filteredItems.value.length === 0 || vm.filteredItems.value.every((item) => vm.isItemProcessing(item.id))"
                  @change="vm.toggleSelectAll()"
                >
              </th>
              <th>Name</th>
              <th>Original Location</th>
              <th class="w-24">
                Size
              </th>
              <th class="w-40">
                Deleted
              </th>
              <th class="w-24 text-right">
                Actions
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in vm.filteredItems.value"
              :key="item.id"
              class="hover:bg-base-200/50 text-xs"
              :class="{
                'bg-primary/5': vm.isSelected(item.id),
                'opacity-60 pointer-events-none': vm.isItemProcessing(item.id),
              }"
            >
              <!-- Checkbox -->
              <td class="text-center">
                <input
                  type="checkbox"
                  class="checkbox checkbox-xs"
                  :checked="vm.isSelected(item.id)"
                  :disabled="vm.isItemProcessing(item.id)"
                  @change="vm.toggleSelectItem(item.id)"
                >
              </td>

              <!-- Name & Type -->
              <td>
                <div class="flex items-center gap-2 max-w-xs truncate">
                  <Folder
                    v-if="item.type === 'directory'"
                    :size="15"
                    class="text-warning shrink-0"
                  />
                  <File
                    v-else
                    :size="15"
                    class="text-info shrink-0"
                  />
                  <span
                    class="font-medium truncate"
                    :title="item.name"
                  >
                    {{ item.name }}
                  </span>
                </div>
              </td>

              <!-- Original Path -->
              <td class="text-base-content/70">
                <span
                  class="truncate max-w-[200px] block"
                  :title="item.originalPath"
                >
                  {{ item.originalPath }}
                </span>
              </td>

              <!-- Size -->
              <td class="text-base-content/70 whitespace-nowrap">
                {{ item.type === 'directory' ? '—' : Str.humanFileSize(item.size) }}
              </td>

              <!-- Deleted At -->
              <td class="text-base-content/70 whitespace-nowrap">
                {{ formatDate(item.deletedAt) }}
              </td>

              <!-- Actions -->
              <td class="text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-1">
                  <button
                    type="button"
                    class="btn btn-ghost btn-xs btn-square text-success hover:bg-success/10"
                    title="Restore item"
                    :disabled="vm.isItemProcessing(item.id)"
                    @click="vm.restoreItems([item.id])"
                  >
                    <Loader2
                      v-if="vm.getItemProcessingState(item.id) === 'restore'"
                      :size="13"
                      class="animate-spin text-primary"
                    />
                    <RotateCcw
                      v-else
                      :size="13"
                    />
                  </button>
                  <button
                    type="button"
                    class="btn btn-ghost btn-xs btn-square text-error hover:bg-error/10"
                    title="Delete permanently"
                    :disabled="vm.isItemProcessing(item.id)"
                    @click="vm.purgeItems([item.id])"
                  >
                    <Loader2
                      v-if="vm.getItemProcessingState(item.id) === 'purge'"
                      :size="13"
                      class="animate-spin text-error"
                    />
                    <Trash2
                      v-else
                      :size="13"
                    />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Modal Footer -->
      <div class="p-3 border-t border-base-content/10 flex items-center justify-between shrink-0 bg-base-100">
        <span class="text-xs text-base-content/50">
          <template v-if="vm.selectedIds.value.length > 0">
            {{ vm.selectedIds.value.length }} of {{ vm.filteredItems.value.length }} selected
          </template>
          <template v-else>
            {{ vm.items.value.length }} total {{ vm.items.value.length === 1 ? 'item' : 'items' }}
          </template>
        </span>
        <button
          type="button"
          class="btn btn-sm"
          @click="onClose"
        >
          Close
        </button>
      </div>
    </div>

    <!-- Backdrop -->
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
