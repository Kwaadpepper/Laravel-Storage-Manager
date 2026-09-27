<script setup lang="ts">
defineOptions({
  name: 'ViewPdf',
});
import { Download, ExternalLink, FileText } from 'lucide-vue-next';
import { onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps<{
  blob: Blob;
  fileName: string;
}>();

const objectUrl = ref<string | null>(null);

watch(
  () => props.blob,
  (newBlob) => {
    if (objectUrl.value) {
      URL.revokeObjectURL(objectUrl.value);
    }
    const pdfBlob =
      newBlob.type === 'application/pdf'
        ? newBlob
        : new Blob([newBlob], { type: 'application/pdf' });
    objectUrl.value = URL.createObjectURL(pdfBlob);
  },
  { immediate: true }
);

onBeforeUnmount(() => {
  if (objectUrl.value) {
    URL.revokeObjectURL(objectUrl.value);
  }
});
</script>

<template>
  <div
    v-if="objectUrl"
    class="flex flex-col h-full gap-2"
  >
    <div class="flex items-center justify-end gap-2 shrink-0">
      <a
        :href="objectUrl"
        target="_blank"
        rel="noopener noreferrer"
        class="btn btn-xs btn-ghost gap-1"
        title="Open in new tab"
      >
        <ExternalLink :size="14" />
        <span>Open in new tab</span>
      </a>
      <a
        :href="objectUrl"
        :download="fileName"
        class="btn btn-xs btn-ghost gap-1"
        title="Download"
      >
        <Download :size="14" />
        <span>Download</span>
      </a>
    </div>

    <div class="flex-1 min-h-0 w-full rounded border border-base-content/10 overflow-hidden bg-base-200">
      <object
        :data="objectUrl"
        type="application/pdf"
        class="w-full h-full"
        :title="fileName"
      >
        <div class="flex flex-col items-center justify-center h-full gap-4 p-6 text-center text-base-content/70">
          <FileText
            :size="48"
            class="text-base-content/40"
          />
          <p class="font-medium">
            PDF preview is not supported directly in your browser.
          </p>
          <div class="flex items-center gap-2">
            <a
              :href="objectUrl"
              target="_blank"
              rel="noopener noreferrer"
              class="btn btn-sm btn-outline gap-1"
            >
              <ExternalLink :size="16" />
              <span>Open in new tab</span>
            </a>
            <a
              :href="objectUrl"
              :download="fileName"
              class="btn btn-sm btn-primary gap-1"
            >
              <Download :size="16" />
              <span>Download PDF</span>
            </a>
          </div>
        </div>
      </object>
    </div>
  </div>
</template>
