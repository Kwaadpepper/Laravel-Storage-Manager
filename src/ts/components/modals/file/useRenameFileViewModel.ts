import { computed, ref, watch, type ComputedRef, type Ref } from 'vue';
import { useFileManager, useEventQueue, type RenameFileValidationField } from '@ts/composables';
import { ModalState, useUiStore } from '@ts/stores';
import { handleFormSubmission } from '@ts/tools';
import { z } from 'zod/v4';

export const baseNameInputName = 'baseName' as const;
export const extensionInputName = 'extension' as const;
export const baseNameMinLength = 1;
export const baseNameMaxLength = 255;
export const extensionMaxLength = 255;

export const formSchema = z.object({
  baseName: z
    .string()
    .min(baseNameMinLength, `Base name must be at least ${baseNameMinLength} character`)
    .max(baseNameMaxLength, `Base name must be at most ${baseNameMaxLength} characters`),
  extension: z
    .string()
    .max(extensionMaxLength, `Extension must be at most ${extensionMaxLength} characters`),
});

export type FormData = z.infer<typeof formSchema>;

export interface RenameFileViewModel {
  isOpen: ComputedRef<boolean>;
  targetFilePath: ComputedRef<string | null>;
  targetBaseName: ComputedRef<string>;
  targetExtension: ComputedRef<string>;
  baseName: Ref<string>;
  extension: Ref<string>;
  formErrorMessage: Ref<string | null>;
  baseNameFieldError: Ref<string | null>;
  extensionFieldError: Ref<string | null>;
  isSubmitting: Ref<boolean>;
  submit: () => Promise<boolean>;
  close: () => void;
}

export function useRenameFileViewModel(): RenameFileViewModel {
  const uiStore = useUiStore();
  const fileManager = useFileManager();
  const { pushAndWait } = useEventQueue();

  const isOpen = computed(() => uiStore.renameFileModal === ModalState.Opened);
  const targetFilePath = computed(() => uiStore.targetFilePath);
  const targetName = computed(() => targetFilePath.value?.split('/').pop() ?? '');
  const lastDot = computed(() => targetName.value.lastIndexOf('.'));
  const targetBaseName = computed(() =>
    lastDot.value > 0 ? targetName.value.slice(0, lastDot.value) : targetName.value
  );
  const targetExtension = computed(() =>
    lastDot.value > 0 ? targetName.value.slice(lastDot.value + 1) : ''
  );

  const baseName = ref('');
  const extension = ref('');
  const formErrorMessage = ref<string | null>(null);
  const baseNameFieldError = ref<string | null>(null);
  const extensionFieldError = ref<string | null>(null);
  const isSubmitting = ref(false);

  watch(
    () => uiStore.renameFileModal,
    (modalState) => {
      if (modalState === ModalState.Opened) {
        baseName.value = targetBaseName.value;
        extension.value = targetExtension.value;
        formErrorMessage.value = null;
        baseNameFieldError.value = null;
        extensionFieldError.value = null;
      }
    }
  );

  watch([baseName, extension], () => {
    if (baseNameFieldError.value) baseNameFieldError.value = null;
    if (extensionFieldError.value) extensionFieldError.value = null;
    if (formErrorMessage.value) formErrorMessage.value = null;
  });

  async function submit(): Promise<boolean> {
    if (isSubmitting.value) return false;
    const curPath = targetFilePath.value;
    if (!curPath) return false;

    formErrorMessage.value = null;
    baseNameFieldError.value = null;
    extensionFieldError.value = null;

    const parseResult = formSchema.safeParse({
      baseName: baseName.value,
      extension: extension.value,
    });

    if (!parseResult.success) {
      for (const issue of parseResult.error.issues) {
        if (issue.path.includes('baseName')) {
          baseNameFieldError.value = issue.message;
        }
        if (issue.path.includes('extension')) {
          extensionFieldError.value = issue.message;
        }
      }
      return false;
    }

    const trimmedExtension = parseResult.data.extension.trim();
    const newName = trimmedExtension
      ? `${parseResult.data.baseName}.${trimmedExtension}`
      : parseResult.data.baseName;

    return handleFormSubmission<RenameFileValidationField>({
      execute: async () => {
        await pushAndWait({
          id: `rename-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`,
          type: 'RENAME',
          sourcePath: curPath,
          destinationPath: curPath.substring(0, curPath.lastIndexOf('/')) + '/' + newName,
          execute: async (): Promise<void> => {
            await fileManager.renameFile(curPath, newName);
          },
        });
      },
      isSubmitting,
      formErrorMessage,
      fieldErrors: { fileName: baseNameFieldError },
      domainErrorField: baseNameFieldError,
      fallbackMessage: 'Failed to rename file',
      onSuccess: close,
    });
  }

  function close(): void {
    baseName.value = '';
    extension.value = '';
    formErrorMessage.value = null;
    baseNameFieldError.value = null;
    extensionFieldError.value = null;
    isSubmitting.value = false;
    uiStore.renameFileModal = ModalState.Closed;
  }

  return {
    isOpen,
    targetFilePath,
    targetBaseName,
    targetExtension,
    baseName,
    extension,
    formErrorMessage,
    baseNameFieldError,
    extensionFieldError,
    isSubmitting,
    submit,
    close,
  };
}
