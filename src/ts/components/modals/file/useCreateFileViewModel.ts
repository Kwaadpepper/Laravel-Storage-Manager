import { computed, ref, type ComputedRef, type Ref } from 'vue';
import { useService } from '@ts/service-container';
import { ModalState, useFileManagerStore, useUiStore } from '@ts/stores';
import { handleFormSubmission } from '@ts/tools';
import type { Path } from '@ts/types';
import { z } from 'zod/v4';

export const baseNameInputName = 'baseName' as const;
export const extensionInputName = 'extension' as const;
export const baseNameMinLength = 1;
export const baseNameMaxLength = 255;
export const extensionMaxLength = 255;

export const formSchema = z.object({
  baseName: z.string().min(baseNameMinLength).max(baseNameMaxLength),
  extension: z.string().max(extensionMaxLength),
});

export type FormData = z.infer<typeof formSchema>;

export interface ValidationError {
  field: 'baseName' | 'extension';
  message: string;
}

export interface CreateFileViewModel {
  baseName: Ref<string | null>;
  extension: Ref<string | null>;
  isOpen: ComputedRef<boolean>;
  currentPath: ComputedRef<Path>;
  formErrorMessage: Ref<string | null>;
  validateFileName: (inputBaseName: string, inputExtension: string) => Promise<ValidationError[] | null>;
  submit: (inputBaseName: string, inputExtension: string, content?: string) => Promise<boolean>;
  close: () => void;
  baseNameInputName: typeof baseNameInputName;
  baseNameMinLength: typeof baseNameMinLength;
  baseNameMaxLength: typeof baseNameMaxLength;
  extensionMaxLength: typeof extensionMaxLength;
}

export function useCreateFileViewModel(): CreateFileViewModel {
  const fileManagerStore = useFileManagerStore();
  const uiStore = useUiStore();
  const fileManagerService = useService('fileManagerService');
  const eventQueueService = useService('eventQueueService');

  const baseName = ref<string | null>(null);
  const extension = ref<string | null>(null);
  const formErrorMessage = ref<string | null>(null);

  const isOpen = computed(() => uiStore.createFileModal === ModalState.Opened);
  const currentPath = computed(() => fileManagerStore.currentPath);

  async function validateFileName(
    inputBaseName: string,
    inputExtension: string
  ): Promise<ValidationError[] | null> {
    const parseResult = formSchema.safeParse({ baseName: inputBaseName, extension: inputExtension });
    if (!parseResult.success) {
      return parseResult.error.issues.flatMap((issue) => {
        const fieldName = issue.path[0];
        if (fieldName === baseNameInputName || fieldName === extensionInputName) {
          return [{ field: fieldName, message: issue.message }];
        }
        return [];
      });
    }

    const trimmedExtension = inputExtension.trim();
    const newName = trimmedExtension ? `${inputBaseName}.${trimmedExtension}` : inputBaseName;
    const candidatePath = `${currentPath.value.replace(/\/$/, '')}/${newName}` as Path;

    const exists = await fileManagerService.fileExists(candidatePath);
    if (exists) {
      return [{ field: 'baseName', message: 'A file with this name already exists.' }];
    }

    return null;
  }

  async function submit(
    inputBaseName: string,
    inputExtension: string,
    content: string = ''
  ): Promise<boolean> {
    formErrorMessage.value = null;

    const trimmedExtension = inputExtension.trim();
    const newName = trimmedExtension ? `${inputBaseName}.${trimmedExtension}` : inputBaseName;
    const curPath = currentPath.value;

    return handleFormSubmission({
      execute: async () => {
        await eventQueueService.pushAndWait({
          id: `create-file-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`,
          type: 'CREATE',
          sourcePath: curPath,
          destinationPath: `${curPath === '/' ? '' : curPath}/${newName}`,
          execute: async (): Promise<void> => {
            await fileManagerService.createFile(curPath, newName, content);
          },
        });
      },
      formErrorMessage,
      fallbackMessage: 'Failed to create file',
      onSuccess: () => {
        uiStore.createFileModal = ModalState.Closed;
      },
    });
  }

  function close(): void {
    baseName.value = null;
    extension.value = null;
    formErrorMessage.value = null;
    uiStore.createFileModal = ModalState.Closed;
  }

  return {
    baseName,
    extension,
    isOpen,
    currentPath,
    formErrorMessage,
    validateFileName,
    submit,
    close,
    baseNameInputName,
    baseNameMinLength,
    baseNameMaxLength,
    extensionMaxLength,
  };
}
