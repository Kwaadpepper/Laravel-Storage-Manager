<script setup lang="ts">
defineOptions({
  name: 'UploadModal',
});
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { ModalState, useDiskStore, useFileManagerStore, useUiStore, useUploadStore, type UploadItem } from '@ts/stores';
import { useService } from '@ts/service-container';
import { CheckCircle2, Clock, FileUp, FolderOpen, Loader2, X, XCircle } from 'lucide-vue-next';

const uiStore = useUiStore();
const diskStore = useDiskStore();
const fileManagerStore = useFileManagerStore();
const uploadStore = useUploadStore();
const uploadService = useService('uploadService');

const fileInputRef = ref<HTMLInputElement | null>(null);
const isDraggingOver = ref(false);

const isOpen = computed(() => uiStore.uploadFileModal === ModalState.Opened);

const hasActiveUploads = computed(() =>
  uploadStore.uploads.some((u) => ['pending', 'uploading', 'assembling'].includes(u.status))
);

const onClose = (): void => {
  uiStore.setUploadFileModal(ModalState.Closed);
};

const onBackdropClose = (): void => {
  if (!hasActiveUploads.value) {
    onClose();
  }
};

onMounted(() => {
  const handleKeydown = (e: KeyboardEvent): void => {
    if (e.key === 'Escape') {
      onBackdropClose();
    }
  };
  window.addEventListener('keydown', handleKeydown);
  onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
  });
});

const onFileSelect = (e: Event): void => {
  const target = e.target as HTMLInputElement;
  if (target.files && target.files.length > 0 && diskStore.currentDisk) {
    uploadService.enqueueUploads(Array.from(target.files), fileManagerStore.currentPath, diskStore.currentDisk);
  }
  target.value = '';
};

const onDragOver = (e: DragEvent): void => {
  e.preventDefault();
  if (e.dataTransfer?.types.includes('Files')) {
    isDraggingOver.value = true;
  }
};

const onDragLeave = (e: DragEvent): void => {
  e.preventDefault();
  isDraggingOver.value = false;
};

const onDrop = (e: DragEvent): void => {
  e.preventDefault();
  isDraggingOver.value = false;
  if (e.dataTransfer?.files && e.dataTransfer.files.length > 0 && diskStore.currentDisk) {
    uploadService.enqueueUploads(Array.from(e.dataTransfer.files), fileManagerStore.currentPath, diskStore.currentDisk);
  }
};

const activeUploads = computed(() =>
  uploadStore.uploads.filter((u) => ['uploading', 'assembling'].includes(u.status)).slice().reverse()
);
const finishedUploads = computed(() =>
  uploadStore.uploads.filter((u) => ['success', 'error'].includes(u.status)).slice().reverse()
);
const pendingUploads = computed(() =>
  uploadStore.uploads.filter((u) => u.status === 'pending').slice().reverse()
);
const mainList = computed(() => [...activeUploads.value, ...finishedUploads.value]);
</script>

<template>
  <dialog
    class="modal"
    :class="{ 'modal-open': isOpen }"
  >
    <div class="modal-box max-w-2xl flex flex-col max-h-[80vh]">
      <form method="dialog">
        <button
          class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2"
          type="button"
          @click="onClose"
        >
          <X :size="18" />
        </button>
      </form>
      <h3 class="font-bold text-lg flex items-center gap-2 mb-4">
        <FileUp />
        Upload Files
      </h3>

      <!-- Dropzone -->
      <div
        class="border-2 border-dashed rounded-xl p-8 flex flex-col items-center justify-center transition-colors cursor-pointer text-center"
        :class="isDraggingOver ? 'border-primary bg-primary/10 text-primary' : 'border-base-300 hover:border-primary/50 hover:bg-base-200'"
        @click="fileInputRef?.click()"
        @dragover="onDragOver"
        @dragleave="onDragLeave"
        @drop="onDrop"
      >
        <input
          ref="fileInputRef"
          type="file"
          multiple
          class="hidden"
          @change="onFileSelect"
        >
        <FolderOpen
          :size="48"
          class="mb-2 opacity-70"
        />
        <p class="font-medium text-lg">
          Click to browse or drop files here
        </p>
        <p class="text-sm opacity-70">
          Uploading to: {{ fileManagerStore.currentPath }}
        </p>
      </div>

      <!-- Upload list -->
      <div class="mt-6 flex-1 min-h-0 flex flex-col">
        <div class="flex items-center justify-between mb-2">
          <h4 class="font-semibold text-sm">
            Upload Tasks
          </h4>
          <button
            class="btn btn-xs btn-ghost text-base-content/60"
            :disabled="uploadStore.uploads.every((u: UploadItem) => ['pending', 'uploading', 'assembling'].includes(u.status))"
            @click="uploadStore.clearCompleted"
          >
            Clear Done
          </button>
        </div>
        <div class="overflow-y-auto flex-1 pr-2 space-y-4">
          <div
            v-if="pendingUploads.length > 0"
            class="collapse collapse-arrow bg-base-200/30 border border-base-300 rounded-lg"
          >
            <input type="checkbox">
            <div class="collapse-title font-medium text-sm text-base-content/70 py-3 min-h-0">
              Fichiers en attente ({{ pendingUploads.length }})
            </div>
            <div class="collapse-content space-y-2">
              <div
                v-for="upload in pendingUploads"
                :key="upload.id"
                class="bg-base-200/50 p-3 rounded-lg flex flex-col gap-2"
              >
                <div class="flex justify-between items-center">
                  <div class="min-w-0 flex flex-wrap items-center gap-2 pr-2">
                    <span
                      class="font-medium truncate text-sm"
                      :title="upload.fileName"
                    >{{ upload.fileName }}</span>
                    <span
                      v-if="upload.finalFileName && upload.finalFileName !== upload.fileName"
                      class="text-xs opacity-70 truncate"
                      :title="`Saved as: ${upload.finalFileName}`"
                    >
                      &rarr; {{ upload.finalFileName }}
                    </span>
                    <span
                      class="badge badge-outline badge-sm shrink-0"
                      :title="`Upload disk: ${upload.disk}`"
                    >
                      {{ upload.disk }}
                    </span>
                  </div>
                  <div class="flex items-center gap-2 shrink-0">
                    <span class="text-xs opacity-50 italic">En attente...</span>
                    <Clock
                      class="text-base-content/50"
                      :size="16"
                    />
                  </div>
                </div>
                <progress
                  class="progress w-full opacity-20"
                  value="0"
                  max="100"
                />
              </div>
            </div>
          </div>

          <div
            v-if="mainList.length > 0"
            class="space-y-2"
          >
            <div
              v-for="upload in mainList"
              :key="upload.id"
              class="bg-base-200/50 p-3 rounded-lg flex flex-col gap-2"
            >
              <div class="flex justify-between items-center">
                <div class="min-w-0 flex flex-wrap items-center gap-2 pr-2">
                  <span
                    class="font-medium truncate text-sm"
                    :title="upload.fileName"
                  >{{ upload.fileName }}</span>
                  <span
                    v-if="upload.finalFileName && upload.finalFileName !== upload.fileName"
                    class="text-xs opacity-70 truncate"
                    :title="`Saved as: ${upload.finalFileName}`"
                  >
                    &rarr; {{ upload.finalFileName }}
                  </span>
                  <span
                    class="badge badge-outline badge-sm shrink-0"
                    :title="`Upload disk: ${upload.disk}`"
                  >
                    {{ upload.disk }}
                  </span>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                  <template v-if="['uploading', 'assembling'].includes(upload.status)">
                    <span class="text-xs opacity-70">{{ upload.progress }}%</span>
                    <Loader2
                      class="animate-spin text-primary"
                      :size="16"
                    />
                  </template>
                  <CheckCircle2
                    v-else-if="upload.status === 'success'"
                    class="text-success"
                    :size="16"
                  />
                  <XCircle
                    v-else-if="upload.status === 'error'"
                    class="text-error"
                    :size="16"
                  />
                </div>
              </div>
              <progress
                v-if="['uploading', 'assembling'].includes(upload.status)"
                class="progress w-full"
                :class="upload.status === 'assembling' ? 'progress-secondary' : 'progress-primary'"
                :value="upload.progress"
                max="100"
              />
              <span
                v-if="upload.status === 'error' && upload.error"
                class="text-xs text-error truncate"
              >
                {{ upload.error }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <form
      method="dialog"
      class="modal-backdrop"
    >
      <button
        type="button"
        @click="onBackdropClose"
      >
        close
      </button>
    </form>
  </dialog>
</template>
