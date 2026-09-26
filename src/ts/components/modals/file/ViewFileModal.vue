<script setup lang="ts">
defineOptions({
  name: 'ViewFileModal',
});
import { computed, onMounted, onUnmounted } from 'vue';
import { Loader, X } from 'lucide-vue-next';
import {
  SUPPORTED_AUDIO_EXTENSIONS as AUDIO_EXTENSIONS,
  SUPPORTED_IMAGE_EXTENSIONS as IMAGE_EXTENSIONS,
  SUPPORTED_TEXT_EXTENSIONS as TEXT_EXTENSIONS,
} from '../../../utils/supported-extensions';
import ViewAudio from './ViewAudio.vue';
import ViewImage from './ViewImage.vue';
import ViewText from './ViewText.vue';
import ViewUnsupported from './ViewUnsupported.vue';
import { useViewFileViewModel } from './useViewFileViewModel';

type ViewerType = 'text' | 'image' | 'audio' | 'unsupported';

function detectViewer(extension: string | null): ViewerType {
  if (!extension) return 'unsupported';
  const ext = extension.toLowerCase();
  if (IMAGE_EXTENSIONS.includes(ext)) return 'image';
  if (AUDIO_EXTENSIONS.includes(ext)) return 'audio';
  if (TEXT_EXTENSIONS.includes(ext)) return 'text';
  return 'unsupported';
}

const { isOpen, close, targetFilePath, blob, loading, error } = useViewFileViewModel();

const fileName = computed(() => (targetFilePath.value ? targetFilePath.value.split('/').pop() ?? '' : ''));
const extension = computed(() =>
  fileName.value.includes('.') ? fileName.value.split('.').pop()?.toLowerCase() ?? null : null
);
const viewerType = computed(() => detectViewer(extension.value));

onMounted(() => {
  const handleKeydown = (e: KeyboardEvent): void => {
    if (e.key === 'Escape') {
      close();
    }
  };
  window.addEventListener('keydown', handleKeydown);
  onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
  });
});
</script>

<template>
  <dialog
    class="modal"
    :class="{ 'modal-open': isOpen }"
    @close="close"
  >
    <div
      class="modal-box flex flex-col"
      style="height: 90vh; width: 90vw; max-width: 90vw"
    >
      <div class="flex items-center justify-between mb-2">
        <h3 class="font-bold text-lg truncate">
          {{ fileName || 'View File' }}
        </h3>
        <button
          class="btn btn-sm btn-circle btn-ghost shrink-0"
          type="button"
          aria-label="Close"
          @click="close"
        >
          <X :size="16" />
        </button>
      </div>

      <div class="flex-1 min-h-0">
        <div
          v-if="loading"
          class="flex items-center justify-center h-full text-base-content/50 gap-2"
        >
          <Loader
            :size="20"
            class="animate-spin"
          />
          <span>Loading...</span>
        </div>

        <div
          v-else-if="error"
          class="flex items-center justify-center h-full text-error"
        >
          {{ error }}
        </div>

        <template v-else-if="blob">
          <ViewText
            v-if="viewerType === 'text'"
            :blob="blob"
            :extension="extension"
          />
          <ViewImage
            v-else-if="viewerType === 'image'"
            :blob="blob"
            :file-name="fileName"
          />
          <ViewAudio
            v-else-if="viewerType === 'audio'"
            :blob="blob"
            :file-name="fileName"
          />
          <ViewUnsupported
            v-else-if="viewerType === 'unsupported'"
            :extension="extension"
          />
        </template>
      </div>

      <div class="flex justify-end mt-2">
        <button
          class="btn"
          type="button"
          @click="close"
        >
          Close
        </button>
      </div>
    </div>

    <form
      method="dialog"
      class="modal-backdrop"
    >
      <button
        type="button"
        @click="close"
      >
        Close
      </button>
    </form>
  </dialog>
</template>
