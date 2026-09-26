<script setup lang="ts">
defineOptions({
  name: 'ViewText',
});
import { defineAsyncComponent, ref, watch } from 'vue';
import { SUPPORTED_LANGUAGES } from '@ts/utils/supported-languages';

const TextEditor = defineAsyncComponent(() => import('@ts/components/editor/TextEditor.vue'));

const props = defineProps<{
  blob: Blob;
  extension: string | null;
}>();

function detectLanguage(extension: string | null): keyof typeof SUPPORTED_LANGUAGES {
  if (!extension) return 'Text';
  const ext = extension.toLowerCase();
  const keys = Object.keys(SUPPORTED_LANGUAGES) as (keyof typeof SUPPORTED_LANGUAGES)[];
  return keys.find((label) => (SUPPORTED_LANGUAGES[label] as readonly string[]).includes(ext)) ?? 'Text';
}

const content = ref('');
const language = detectLanguage(props.extension);

watch(
  () => props.blob,
  (newBlob) => {
    newBlob
      .text()
      .then((text) => {
        content.value = text;
      })
      .catch(() => {
        content.value = '';
      });
  },
  { immediate: true }
);
</script>

<template>
  <div class="h-full overflow-hidden">
    <Suspense>
      <template #default>
        <TextEditor
          :model-value="content"
          :language="language"
          read-only
        />
      </template>
      <template #fallback>
        <div class="flex items-center justify-center h-full text-base-content/50">
          Loading...
        </div>
      </template>
    </Suspense>
  </div>
</template>
