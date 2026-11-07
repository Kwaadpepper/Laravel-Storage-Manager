import { computed, ref, watch, type ComputedRef, type Ref } from 'vue';
import { useFileManager, useEventQueue, type CreateDirectoryValidationField } from '@ts/composables';
import { ModalState, useFileManagerStore, useUiStore } from '@ts/stores';
import { handleFormSubmission } from '@ts/tools';
import { z } from 'zod/v4';

export const directoryNameInputName = 'directoryName' as const;
export const directoryNameMinLength = 1;
export const directoryNameMaxLength = 255;

export const formSchema = z.object({
  directoryName: z
    .string()
    .min(directoryNameMinLength, `Directory name must be at least ${directoryNameMinLength} character`)
    .max(directoryNameMaxLength, `Directory name must be at most ${directoryNameMaxLength} characters`),
});

export type FormData = z.infer<typeof formSchema>;

export interface CreateDirectoryViewModel {
  isOpen: ComputedRef<boolean>;
  currentPath: ComputedRef<string>;
  directoryName: Ref<string>;
  formErrorMessage: Ref<string | null>;
  directoryNameFieldError: Ref<string | null>;
  isSubmitting: Ref<boolean>;
  submit: () => Promise<boolean>;
  close: () => void;
}

export function useCreateDirectoryViewModel(): CreateDirectoryViewModel {
  const fileManagerStore = useFileManagerStore();
  const uiStore = useUiStore();
  const fileManager = useFileManager();
  const { pushAndWait } = useEventQueue();

  const activePath = computed(() => uiStore.targetDirectoryPath ?? fileManagerStore.currentPath);
  const isOpen = computed(() => uiStore.newDirectoryModal === ModalState.Opened);

  const directoryName = ref('');
  const formErrorMessage = ref<string | null>(null);
  const directoryNameFieldError = ref<string | null>(null);
  const isSubmitting = ref(false);

  watch(directoryName, () => {
    if (directoryNameFieldError.value) directoryNameFieldError.value = null;
    if (formErrorMessage.value) formErrorMessage.value = null;
  });

  async function submit(): Promise<boolean> {
    if (isSubmitting.value) return false;
    formErrorMessage.value = null;
    directoryNameFieldError.value = null;

    const parseResult = formSchema.safeParse({ directoryName: directoryName.value });
    if (!parseResult.success) {
      const issue = parseResult.error.issues.find((i) => i.path.includes('directoryName'));
      directoryNameFieldError.value = issue?.message ?? 'Invalid directory name';
      return false;
    }

    const validName = parseResult.data.directoryName;
    const basePath = activePath.value;

    return handleFormSubmission<CreateDirectoryValidationField>({
      execute: async () => {
        await pushAndWait({
          id: `create-dir-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`,
          type: 'CREATE',
          sourcePath: basePath,
          destinationPath: `${basePath === '/' ? '' : basePath}/${validName}`,
          execute: async (): Promise<void> => {
            await fileManager.createDirectory(basePath, validName);
          },
        });
      },
      isSubmitting,
      formErrorMessage,
      fieldErrors: { directoryName: directoryNameFieldError },
      domainErrorField: directoryNameFieldError,
      fallbackMessage: 'Failed to create directory',
      onSuccess: close,
    });
  }

  function close(): void {
    directoryName.value = '';
    formErrorMessage.value = null;
    directoryNameFieldError.value = null;
    isSubmitting.value = false;
    uiStore.newDirectoryModal = ModalState.Closed;
    uiStore.targetDirectoryPath = null;
  }

  return {
    isOpen,
    currentPath: activePath,
    directoryName,
    formErrorMessage,
    directoryNameFieldError,
    isSubmitting,
    submit,
    close,
  };
}
