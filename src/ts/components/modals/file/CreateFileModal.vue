<script setup lang="ts">
defineOptions({
  name: 'CreateFileModal',
});
import { computed, defineAsyncComponent, ref } from 'vue';
import { X } from 'lucide-vue-next';
import { SUPPORTED_LANGUAGES } from '@ts/utils/supported-languages';
import { useEscapeClose } from '@ts/tools';
import CreateFileAskName from './CreateFileAskName.vue';
import { useCreateFileViewModel, type ValidationError } from './useCreateFileViewModel';

const TextEditor = defineAsyncComponent(() => import('@ts/components/editor/TextEditor.vue'));

function detectLanguageFromExtension(ext: string): keyof typeof SUPPORTED_LANGUAGES {
  const normalized = ext.toLowerCase().replace(/^\./, '');
  for (const [label, aliases] of Object.entries(SUPPORTED_LANGUAGES)) {
    if ((aliases as readonly string[]).includes(normalized)) {
      return label as keyof typeof SUPPORTED_LANGUAGES;
    }
  }
  return 'Text';
}

const {
  isOpen,
  close,
  baseName,
  extension,
  baseNameMinLength,
  baseNameMaxLength,
  extensionMaxLength,
  validateFileName,
  submit,
} = useCreateFileViewModel();

const step = ref<'name' | 'editor'>('name');
const fileContent = ref('');
const language = ref<keyof typeof SUPPORTED_LANGUAGES>('Text');

function onClose(): void {
  baseName.value = null;
  extension.value = null;
  fileContent.value = '';
  language.value = 'Text';
  step.value = 'name';
  close();
}

useEscapeClose(onClose);

async function onValidateFileName(name: string, ext: string): Promise<ValidationError[] | null> {
  const errors = await validateFileName(name, ext);
  return errors ? errors.map(({ field, message }) => ({ field, message })) : null;
}

function onConfirmFileName(confirmedBaseName: string, confirmedExtension: string): void {
  baseName.value = confirmedBaseName;
  extension.value = confirmedExtension;
  language.value = detectLanguageFromExtension(confirmedExtension);
  step.value = 'editor';
}

function onCancelFileName(): void {
  close();
}

async function onCreate(): Promise<void> {
  if (baseName.value === null || extension.value === null) return;
  await submit(baseName.value, extension.value, fileContent.value);
}

const fileName = computed(() => {
  if (baseName.value === null) return null;
  return extension.value ? `${baseName.value}.${extension.value}` : baseName.value;
});
</script>

<template>
  <dialog
    class="modal"
    :class="{ 'modal-open': isOpen }"
    @close="onClose"
  >
    <div
      class="modal-box flex flex-col"
      :style="step === 'editor' ? { height: '90vh', maxHeight: '90vh', width: '90vw', maxWidth: '90vw', overflow: 'hidden' } : {}"
    >
      <div class="flex items-center justify-between mb-2 shrink-0">
        <h3 class="font-bold text-lg">
          {{ fileName ? `Create - ${fileName}` : 'Create File' }}
        </h3>
        <button
          class="btn btn-sm btn-circle btn-ghost"
          type="button"
          @click="onClose"
        >
          <X :size="16" />
        </button>
      </div>

      <CreateFileAskName
        v-if="step === 'name'"
        :base-name="baseName ?? ''"
        :extension="extension ?? ''"
        :base-name-min-length="baseNameMinLength"
        :base-name-max-length="baseNameMaxLength"
        :extension-max-length="extensionMaxLength"
        :on-validate="onValidateFileName"
        @confirm="onConfirmFileName"
        @cancel="onCancelFileName"
      />

      <template v-else-if="step === 'editor'">
        <div class="flex-1 min-h-0 overflow-hidden">
          <Suspense>
            <template #default>
              <TextEditor
                v-model="fileContent"
                v-model:language="language"
              />
            </template>
            <template #fallback>
              <div class="flex items-center justify-center h-full text-base-content/50">
                Loading editor...
              </div>
            </template>
          </Suspense>
        </div>

        <div class="flex justify-end gap-2 mt-2 shrink-0">
          <button
            class="btn btn-primary"
            type="button"
            @click="void onCreate()"
          >
            Create File
          </button>
          <button
            class="btn"
            type="button"
            @click="onClose"
          >
            Cancel
          </button>
        </div>
      </template>
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
