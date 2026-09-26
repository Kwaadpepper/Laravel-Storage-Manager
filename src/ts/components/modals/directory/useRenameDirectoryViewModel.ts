import { computed, ref, watch, type ComputedRef, type Ref } from 'vue';
import { isDomainValidationError, isValidationError } from '@ts/errors';
import type { RenameDirectoryValidationField } from '@ts/services';
import { useService } from '@ts/service-container';
import { ModalState, useUiStore } from '@ts/stores';
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

export interface RenameDirectoryViewModel {
  isOpen: ComputedRef<boolean>;
  targetDirectoryPath: ComputedRef<string | null>;
  targetName: ComputedRef<string>;
  directoryName: Ref<string>;
  formErrorMessage: Ref<string | null>;
  directoryNameFieldError: Ref<string | null>;
  isSubmitting: Ref<boolean>;
  submit: () => Promise<boolean>;
  close: () => void;
}

export function useRenameDirectoryViewModel(): RenameDirectoryViewModel {
  const uiStore = useUiStore();
  const fileManagerService = useService('fileManagerService');
  const eventQueueService = useService('eventQueueService');

  const isOpen = computed(() => uiStore.renameDirectoryModal === ModalState.Opened);
  const targetDirectoryPath = computed(() => uiStore.targetDirectoryPath);
  const targetName = computed(() => targetDirectoryPath.value?.split('/').pop() ?? '');

  const directoryName = ref('');
  const formErrorMessage = ref<string | null>(null);
  const directoryNameFieldError = ref<string | null>(null);
  const isSubmitting = ref(false);

  watch(
    () => uiStore.renameDirectoryModal,
    (modalState) => {
      if (modalState === ModalState.Opened) {
        directoryName.value = targetName.value;
        formErrorMessage.value = null;
        directoryNameFieldError.value = null;
      }
    }
  );

  watch(directoryName, () => {
    if (directoryNameFieldError.value) directoryNameFieldError.value = null;
    if (formErrorMessage.value) formErrorMessage.value = null;
  });

  async function submit(): Promise<boolean> {
    if (isSubmitting.value) return false;
    const currentTargetPath = targetDirectoryPath.value;
    if (!currentTargetPath) return false;

    formErrorMessage.value = null;
    directoryNameFieldError.value = null;

    const parseResult = formSchema.safeParse({ directoryName: directoryName.value });
    if (!parseResult.success) {
      const issue = parseResult.error.issues.find((i) => i.path.includes('directoryName'));
      directoryNameFieldError.value = issue?.message ?? 'Invalid directory name';
      return false;
    }

    const newName = parseResult.data.directoryName;

    try {
      isSubmitting.value = true;
      await eventQueueService.pushAndWait({
        id: `rename-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`,
        type: 'RENAME',
        sourcePath: currentTargetPath,
        destinationPath: currentTargetPath.substring(0, currentTargetPath.lastIndexOf('/')) + '/' + newName,
        execute: async (): Promise<void> => {
          await fileManagerService.renameDirectory(currentTargetPath, newName);
        },
      });

      close();
      return true;
    } catch (error: unknown) {
      if (isValidationError<RenameDirectoryValidationField>(error)) {
        const fieldErrors = error.getFieldErrors();
        if (fieldErrors.directoryName) {
          directoryNameFieldError.value = fieldErrors.directoryName;
          return false;
        }
      }

      if (isDomainValidationError(error)) {
        directoryNameFieldError.value = error.message;
        return false;
      }

      if (error instanceof Error) {
        formErrorMessage.value = error.message;
        return false;
      }

      formErrorMessage.value = 'Failed to rename directory';
      return false;
    } finally {
      isSubmitting.value = false;
    }
  }

  function close(): void {
    directoryName.value = '';
    formErrorMessage.value = null;
    directoryNameFieldError.value = null;
    isSubmitting.value = false;
    uiStore.setRenameDirectoryModal(ModalState.Closed);
  }

  return {
    isOpen,
    targetDirectoryPath,
    targetName,
    directoryName,
    formErrorMessage,
    directoryNameFieldError,
    isSubmitting,
    submit,
    close,
  };
}
