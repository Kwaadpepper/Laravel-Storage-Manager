<script setup lang="ts">
defineOptions({
  name: 'MoveDirectoryModal',
});
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { FolderPlus, X } from 'lucide-vue-next';
import DirectoryPicker from '@ts/components/shared/DirectoryPicker.vue';
import { useService } from '@ts/service-container';
import { ModalState, useFileManagerStore, useUiStore } from '@ts/stores';
import { rootPath, type Path } from '@ts/types';

const fileManagerService = useService('fileManagerService');
const eventQueueService = useService('eventQueueService');

const uiStore = useUiStore();
const fileManagerStore = useFileManagerStore();

const selectedDestination = ref<Path>(rootPath());
const isSubmitting = ref(false);

const isOpen = computed(() => uiStore.moveModal === ModalState.Opened);
const selectedNodesList = computed(() => Object.values(fileManagerStore.selectedNodes));

function onClose(): void {
  selectedDestination.value = rootPath();
  isSubmitting.value = false;
  uiStore.setMoveModal(ModalState.Closed);
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

function onNewFolder(): void {
  uiStore.setTargetDirectoryPath(selectedDestination.value);
  uiStore.setNewDirectoryModal(ModalState.Opened);
}

async function onMove(): Promise<void> {
  if (selectedNodesList.value.length === 0) return;

  isSubmitting.value = true;

  const dest = selectedDestination.value;
  const events = selectedNodesList.value.map((node) => ({
    id: `move-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`,
    type: 'MOVE' as const,
    sourcePath: node.path,
    destinationPath: dest,
    execute: async (): Promise<void> => {
      await fileManagerService.move(node.path, dest);
    },
  }));

  eventQueueService.pushBatch(events);
  fileManagerStore.selectNodes(); // Clear selection

  isSubmitting.value = false;
  onClose();
}
</script>

<template>
  <dialog
    class="modal"
    :class="{ 'modal-open': isOpen }"
    @close="onClose"
  >
    <div class="modal-box w-11/12 max-w-2xl flex flex-col h-[80vh]">
      <button
        class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2"
        type="button"
        @click="onClose"
      >
        <X :size="16" />
      </button>
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-bold text-lg">
          Move {{ selectedNodesList.length }} item(s) to...
        </h3>
        <button
          type="button"
          class="btn btn-sm btn-ghost gap-1 mr-6"
          title="Create New Folder here"
          @click="onNewFolder"
        >
          <FolderPlus :size="14" />
          <span class="hidden sm:inline">New Folder</span>
        </button>
      </div>

      <div class="flex-1 overflow-hidden relative">
        <DirectoryPicker
          :selected-path="selectedDestination"
          @select="(path) => (selectedDestination = path)"
        />
      </div>

      <div class="mt-4 flex justify-between items-center gap-2">
        <div class="text-sm text-base-content/70 truncate flex-1">
          Destination: <span class="font-mono font-medium text-base-content">{{ selectedDestination }}</span>
        </div>
        <button
          class="btn btn-ghost"
          type="button"
          :disabled="isSubmitting"
          @click="onClose"
        >
          Cancel
        </button>
        <button
          class="btn btn-primary"
          type="button"
          :disabled="isSubmitting || selectedNodesList.length === 0"
          @click="onMove"
        >
          {{ isSubmitting ? 'Moving...' : 'Move Here' }}
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
