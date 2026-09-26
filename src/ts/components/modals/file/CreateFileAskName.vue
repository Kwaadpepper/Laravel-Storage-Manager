<script setup lang="ts">
defineOptions({
  name: 'CreateFileAskName',
});
import { ref } from 'vue';
import { Pencil } from 'lucide-vue-next';
import type { ValidationError } from './useCreateFileViewModel';

const props = withDefaults(
  defineProps<{
    baseName?: string;
    extension?: string;
    baseNameMinLength: number;
    baseNameMaxLength: number;
    extensionMaxLength: number;
    onValidate: (baseName: string, extension: string) => Promise<ValidationError[] | null>;
  }>(),
  {
    baseName: '',
    extension: '',
  }
);

const emit = defineEmits<{
  (e: 'confirm', baseName: string, extension: string): void;
  (e: 'cancel'): void;
}>();

const baseNameValue = ref(props.baseName);
const extensionValue = ref(props.extension);
const baseNameInputRef = ref<HTMLInputElement | null>(null);
const extensionInputRef = ref<HTMLInputElement | null>(null);

async function onSubmit(event: Event): Promise<void> {
  event.preventDefault();
  const submittedBaseName = baseNameValue.value;
  const submittedExtension = extensionValue.value;

  const validationErrors = await props.onValidate(submittedBaseName, submittedExtension);
  if (!validationErrors || validationErrors.length === 0) {
    emit('confirm', submittedBaseName, submittedExtension);
  } else {
    validationErrors.forEach((error) => {
      const input = error.field === 'baseName' ? baseNameInputRef.value : extensionInputRef.value;
      if (input) {
        input.setAttribute('aria-invalid', 'true');
        input.setCustomValidity(error.message);
        input.reportValidity();
      }
    });
  }
}

function onInput(field: 'baseName' | 'extension'): void {
  const input = field === 'baseName' ? baseNameInputRef.value : extensionInputRef.value;
  if (input) {
    input.setCustomValidity('');
    input.removeAttribute('aria-invalid');
  }
}
</script>

<template>
  <form
    class="fieldset flex flex-col justify-center gap-4 py-4"
    @submit="onSubmit"
  >
    <strong class="text-center block my-2">Name your file</strong>
    <div class="flex items-start gap-1 mx-auto">
      <div class="flex flex-col gap-1">
        <label class="input validator">
          <Pencil class="h-[1em] opacity-50" />
          <input
            ref="baseNameInputRef"
            v-model="baseNameValue"
            class="grow"
            name="baseName"
            type="text"
            autofocus
            autocomplete="off"
            :minlength="baseNameMinLength"
            :maxlength="baseNameMaxLength"
            required
            @input="onInput('baseName')"
          >
        </label>
        <small class="text-center opacity-50">{{ `Base name (${baseNameMinLength}-${baseNameMaxLength} chars)` }}</small>
      </div>

      <span class="self-center px-1 text-base-content/60 select-none">.</span>

      <div class="flex flex-col gap-1">
        <label class="input">
          <input
            ref="extensionInputRef"
            v-model="extensionValue"
            class="grow w-24"
            name="extension"
            type="text"
            autocomplete="off"
            :maxlength="extensionMaxLength"
            aria-label="Extension"
            @input="onInput('extension')"
          >
        </label>
        <small class="text-center opacity-50">{{ `Extension (max ${extensionMaxLength} chars)` }}</small>
      </div>
    </div>

    <span class="flex justify-center">
      <button
        class="btn btn-primary me-2"
        type="submit"
      >Next</button>
      <button
        class="btn btn-secondary ms-2"
        type="button"
        @click="emit('cancel')"
      >Cancel</button>
    </span>
  </form>
</template>
