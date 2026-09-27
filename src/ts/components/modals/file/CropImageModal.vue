<script setup lang="ts">
defineOptions({
  name: 'CropImageModal',
});

import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import {
  Crop,
  FlipHorizontal,
  FlipVertical,
  RefreshCw,
  RotateCcw,
  RotateCw,
  X,
  ZoomIn,
  ZoomOut,
} from 'lucide-vue-next';
import { useEscapeClose } from '@ts/tools';
import { useConfigStore, useDiskStore } from '@ts/stores';
import { AspectRatioKey, useCropImageViewModel } from './useCropImageViewModel';

const diskStore = useDiskStore();
const configStore = useConfigStore();
const isReadOnly = computed(() =>
  diskStore.currentDisk ? configStore.readOnlyDisks.includes(diskStore.currentDisk) : false
);
const vm = useCropImageViewModel();
const imageElementRef = ref<HTMLImageElement | null>(null);

function onClose(): void {
  vm.close();
}

useEscapeClose(onClose);

function onImageLoad(): void {
  if (imageElementRef.value) {
    vm.initCropper(imageElementRef.value);
  }
}

onMounted(async () => {
  await nextTick();
  if (imageElementRef.value?.complete && imageElementRef.value.naturalWidth > 0) {
    onImageLoad();
  }
});

onBeforeUnmount(() => {
  vm.destroyCropper();
});

const aspectRatios: { key: AspectRatioKey; label: string }[] = [
  { key: 'free', label: 'Free' },
  { key: '1:1', label: '1:1' },
  { key: '4:3', label: '4:3' },
  { key: '16:9', label: '16:9' },
  { key: '3:2', label: '3:2' },
  { key: '2:3', label: '2:3' },
];
</script>

<template>
  <dialog
    class="modal"
    :class="{ 'modal-open': vm.isOpen.value }"
    @close="onClose"
  >
    <div class="modal-box max-w-5xl w-full p-5 flex flex-col gap-3 max-h-[95vh]">
      <!-- Header -->
      <div class="flex items-center justify-between pb-2 border-b border-base-200">
        <div class="flex items-center gap-2">
          <Crop
            :size="20"
            class="text-primary"
          />
          <h3 class="font-bold text-lg">
            Crop Image
          </h3>
          <span
            v-if="vm.fileName.value"
            class="text-xs text-base-content/60 truncate max-w-xs"
          >
            ({{ vm.fileName.value }})
          </span>
        </div>
        <button
          class="btn btn-sm btn-circle btn-ghost"
          type="button"
          :disabled="vm.isSubmitting.value"
          @click="onClose"
        >
          <X :size="16" />
        </button>
      </div>

      <!-- Error message banner -->
      <div
        v-if="vm.errorMessage.value"
        class="alert alert-error text-sm py-2"
      >
        <span>{{ vm.errorMessage.value }}</span>
      </div>

      <!-- Cropper Viewport -->
      <div
        class="relative w-full h-[460px] md:h-[540px] bg-base-300/40 rounded-lg overflow-hidden flex items-center justify-center select-none [&_cropper-canvas]:w-full [&_cropper-canvas]:h-full"
      >
        <div
          v-if="!vm.isImageLoaded.value"
          class="skeleton absolute inset-0 rounded-lg"
        />
        <img
          v-if="vm.imageSrc.value"
          ref="imageElementRef"
          :src="vm.imageSrc.value"
          :alt="vm.fileName.value"
          crossorigin="anonymous"
          class="max-w-full max-h-full block object-contain"
          @load="onImageLoad"
        >
      </div>

      <!-- Controls Toolbar -->
      <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
        <!-- Aspect Ratios -->
        <div class="flex items-center gap-1">
          <span class="text-xs font-semibold text-base-content/60 mr-1">Ratio:</span>
          <div class="join">
            <button
              v-for="item in aspectRatios"
              :key="item.key"
              type="button"
              class="btn btn-xs join-item"
              :class="vm.aspectRatio.value === item.key ? 'btn-primary' : 'btn-ghost'"
              :disabled="vm.isSubmitting.value || !vm.isImageLoaded.value"
              @click="vm.setAspectRatio(item.key)"
            >
              {{ item.label }}
            </button>
          </div>
        </div>

        <!-- Transformations Toolbar -->
        <div class="flex items-center gap-1">
          <div
            class="tooltip"
            data-tip="Rotate -90°"
          >
            <button
              type="button"
              class="btn btn-xs btn-outline"
              :disabled="vm.isSubmitting.value || !vm.isImageLoaded.value"
              @click="vm.rotate(-90)"
            >
              <RotateCcw :size="14" />
            </button>
          </div>
          <div
            class="tooltip"
            data-tip="Rotate +90°"
          >
            <button
              type="button"
              class="btn btn-xs btn-outline"
              :disabled="vm.isSubmitting.value || !vm.isImageLoaded.value"
              @click="vm.rotate(90)"
            >
              <RotateCw :size="14" />
            </button>
          </div>
          <div
            class="tooltip"
            data-tip="Flip Horizontal"
          >
            <button
              type="button"
              class="btn btn-xs btn-outline"
              :disabled="vm.isSubmitting.value || !vm.isImageLoaded.value"
              @click="vm.flipHorizontal()"
            >
              <FlipHorizontal :size="14" />
            </button>
          </div>
          <div
            class="tooltip"
            data-tip="Flip Vertical"
          >
            <button
              type="button"
              class="btn btn-xs btn-outline"
              :disabled="vm.isSubmitting.value || !vm.isImageLoaded.value"
              @click="vm.flipVertical()"
            >
              <FlipVertical :size="14" />
            </button>
          </div>
          <div
            class="tooltip"
            data-tip="Zoom In"
          >
            <button
              type="button"
              class="btn btn-xs btn-outline"
              :disabled="vm.isSubmitting.value || !vm.isImageLoaded.value"
              @click="vm.zoom(0.1)"
            >
              <ZoomIn :size="14" />
            </button>
          </div>
          <div
            class="tooltip"
            data-tip="Zoom Out"
          >
            <button
              type="button"
              class="btn btn-xs btn-outline"
              :disabled="vm.isSubmitting.value || !vm.isImageLoaded.value"
              @click="vm.zoom(-0.1)"
            >
              <ZoomOut :size="14" />
            </button>
          </div>
          <div
            class="tooltip"
            data-tip="Reset"
          >
            <button
              type="button"
              class="btn btn-xs btn-ghost text-error"
              :disabled="vm.isSubmitting.value || !vm.isImageLoaded.value"
              @click="vm.reset()"
            >
              <RefreshCw :size="14" />
            </button>
          </div>
        </div>
      </div>

      <!-- Save Options -->
      <div class="bg-base-200/50 rounded-lg p-3 flex flex-col gap-2 mt-1">
        <div class="flex items-center gap-4 text-xs font-semibold">
          <label class="flex items-center gap-2 cursor-pointer">
            <input
              v-model="vm.saveMode.value"
              type="radio"
              value="copy"
              class="radio radio-xs radio-primary"
              :disabled="vm.isSubmitting.value"
            >
            <span>Save as copy</span>
          </label>
          <label
            class="flex items-center gap-2 cursor-pointer"
            :class="{ 'opacity-50 cursor-not-allowed': isReadOnly }"
          >
            <input
              v-model="vm.saveMode.value"
              type="radio"
              value="overwrite"
              class="radio radio-xs radio-primary"
              :disabled="vm.isSubmitting.value || isReadOnly"
            >
            <span>Overwrite original</span>
          </label>
        </div>

        <div
          v-if="vm.saveMode.value === 'copy'"
          class="flex items-center gap-2"
        >
          <label class="text-xs text-base-content/70 whitespace-nowrap">File name:</label>
          <input
            v-model="vm.newFileName.value"
            type="text"
            class="input input-sm input-bordered w-full text-xs"
            placeholder="cropped-image.jpg"
            :disabled="vm.isSubmitting.value"
          >
        </div>
        <p
          v-else
          class="text-xs text-warning"
        >
          Warning: The original file "{{ vm.fileName.value }}" will be overwritten.
        </p>
      </div>

      <!-- Modal Footer -->
      <div class="modal-action mt-2">
        <button
          type="button"
          class="btn btn-sm btn-ghost"
          :disabled="vm.isSubmitting.value"
          @click="onClose"
        >
          Cancel
        </button>
        <button
          type="button"
          class="btn btn-sm btn-primary"
          :disabled="vm.isSubmitting.value || !vm.isImageLoaded.value"
          @click="vm.submit()"
        >
          <span
            v-if="vm.isSubmitting.value"
            class="loading loading-spinner loading-xs"
          />
          <Crop
            v-else
            :size="16"
          />
          Save Cropped Image
        </button>
      </div>
    </div>
  </dialog>
</template>

<style scoped>
:deep(cropper-canvas) {
  width: 100% !important;
  height: 100% !important;
  display: block !important;
}

:deep(cropper-image) {
  display: block !important;
}
</style>
