<script setup lang="ts">
defineOptions({
  name: 'FileMetadataModal',
});

import { Info, Loader, X } from 'lucide-vue-next';
import { useEscapeClose } from '@ts/tools';
import { t } from '@ts/i18n';
import FileMetadataPanel from './FileMetadataPanel.vue';
import { useFileMetadataViewModel } from './useFileMetadataViewModel';

const { isOpen, metadata, loading, error, close } = useFileMetadataViewModel();

useEscapeClose(close);
</script>

<template>
  <dialog
    class="modal"
    :class="{ 'modal-open': isOpen }"
    @close="close"
  >
    <div
      class="modal-box flex flex-col p-0 max-h-[85vh] w-11/12 max-w-2xl overflow-hidden"
    >
      <!-- Modal Header -->
      <div class="flex items-center justify-between p-4 border-b border-base-content/10 shrink-0">
        <h3 class="font-bold text-base flex items-center gap-2">
          <Info
            :size="18"
            class="text-primary"
          />
          <span>{{ t('file_metadata_title') }}</span>
        </h3>
        <button
          type="button"
          class="btn btn-sm btn-circle btn-ghost"
          :aria-label="t('close')"
          @click="close"
        >
          <X :size="16" />
        </button>
      </div>

      <!-- Modal Body -->
      <div class="flex-1 min-h-[300px] overflow-hidden flex flex-col">
        <div
          v-if="loading"
          class="flex-1 flex items-center justify-center gap-2 text-base-content/50"
        >
          <Loader
            :size="24"
            class="animate-spin text-primary"
          />
          <span>{{ t('loading') }}</span>
        </div>

        <div
          v-else-if="error"
          class="flex-1 flex items-center justify-center p-6 text-error text-center"
        >
          {{ error }}
        </div>

        <FileMetadataPanel
          v-else-if="metadata"
          :metadata="metadata"
          class="flex-1"
        />
      </div>

      <!-- Modal Footer -->
      <div class="p-3 border-t border-base-content/10 flex justify-end shrink-0 bg-base-100">
        <button
          type="button"
          class="btn btn-sm"
          @click="close"
        >
          {{ t('close') }}
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
        {{ t('close') }}
      </button>
    </form>
  </dialog>
</template>
