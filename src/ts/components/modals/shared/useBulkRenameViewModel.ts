import { computed, ref, type ComputedRef, type Ref } from 'vue';
import { useFileManager, useEventQueue } from '@ts/composables';
import { ModalState, useFileManagerStore, useUiStore } from '@ts/stores';
import { handleFormSubmission } from '@ts/tools';
import { isFile, type Path } from '@ts/types';

export interface BulkRenamePreviewItem {
  from: Path;
  originalName: string;
  newName: string;
  hasConflict: boolean;
}

export interface BulkRenameViewModel {
  isOpen: ComputedRef<boolean>;
  itemCount: ComputedRef<number>;
  pattern: Ref<string>;
  startNumber: Ref<number>;
  padding: Ref<number>;
  preserveExtension: Ref<boolean>;
  previewItems: ComputedRef<BulkRenamePreviewItem[]>;
  hasDuplicates: ComputedRef<boolean>;
  canSubmit: ComputedRef<boolean>;
  isSubmitting: Ref<boolean>;
  formErrorMessage: Ref<string | null>;
  setPatternToken: (token: string) => void;
  submit: () => Promise<boolean>;
  close: () => void;
}

export function useBulkRenameViewModel(): BulkRenameViewModel {
  const uiStore = useUiStore();
  const fileManagerStore = useFileManagerStore();
  const fileManager = useFileManager();
  const { pushAndWait } = useEventQueue();

  const isOpen = computed(() => uiStore.bulkRenameModal === ModalState.Opened);
  const selectedEntries = computed(() => Object.entries(fileManagerStore.selectedNodes));
  const itemCount = computed(() => selectedEntries.value.length);

  const pattern = ref('file_{n}');
  const startNumber = ref(1);
  const padding = ref(1);
  const preserveExtension = ref(true);
  const isSubmitting = ref(false);
  const formErrorMessage = ref<string | null>(null);

  function setPatternToken(token: string): void {
    pattern.value += token;
  }

  function generateNewName(originalName: string, index: number, isFile: boolean): string {
    let baseName = originalName;
    let ext = '';

    const lastDot = originalName.lastIndexOf('.');
    if (isFile && lastDot > 0) {
      baseName = originalName.substring(0, lastDot);
      ext = originalName.substring(lastDot);
    }

    const counter = (startNumber.value + index).toString().padStart(padding.value, '0');
    const today = new Date().toISOString().slice(0, 10);
    const cleanExt = ext.startsWith('.') ? ext.slice(1) : ext;

    let res = pattern.value
      .replace(/\{n\}/g, counter)
      .replace(/\{name\}/g, baseName)
      .replace(/\{date\}/g, today)
      .replace(/\{ext\}/g, cleanExt);

    if (preserveExtension.value && isFile && ext && !pattern.value.includes('{ext}')) {
      res += ext;
    }

    return res;
  }

  const previewItems = computed<BulkRenamePreviewItem[]>(() => {
    const list: Array<{ from: Path; originalName: string; newName: string }> = [];

    selectedEntries.value.forEach(([pathStr, node], index) => {
      const isFileNode = node ? isFile(node) : false;
      const name = node?.name ?? pathStr.split('/').pop() ?? '';
      const newName = generateNewName(name, index, isFileNode);
      list.push({
        from: pathStr as Path,
        originalName: name,
        newName,
      });
    });

    // Check duplicate targets
    const counts = new Map<string, number>();
    for (const item of list) {
      counts.set(item.newName, (counts.get(item.newName) ?? 0) + 1);
    }

    return list.map((item) => ({
      ...item,
      hasConflict: (counts.get(item.newName) ?? 0) > 1,
    }));
  });

  const hasDuplicates = computed(() => previewItems.value.some((item) => item.hasConflict));
  const canSubmit = computed(
    () =>
      itemCount.value > 0 &&
      pattern.value.trim().length > 0 &&
      !hasDuplicates.value &&
      !isSubmitting.value
  );

  async function submit(): Promise<boolean> {
    if (!canSubmit.value) return false;
    formErrorMessage.value = null;

    const renames = previewItems.value.map((item) => ({
      from: item.from,
      to: item.newName,
    }));

    return handleFormSubmission({
      execute: async () => {
        await pushAndWait({
          id: `bulk-rename-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`,
          type: 'RENAME',
          sourcePath: fileManagerStore.currentPath,
          execute: async (): Promise<void> => {
            await fileManager.bulkRename(renames);
          },
        });
      },
      isSubmitting,
      formErrorMessage,
      fieldErrors: {},
      fallbackMessage: 'Failed to rename files',
      onSuccess: () => {
        fileManagerStore.selectNodes();
        close();
      },
    });
  }

  function close(): void {
    pattern.value = 'file_{n}';
    startNumber.value = 1;
    padding.value = 1;
    preserveExtension.value = true;
    formErrorMessage.value = null;
    isSubmitting.value = false;
    uiStore.bulkRenameModal = ModalState.Closed;
  }

  return {
    isOpen,
    itemCount,
    pattern,
    startNumber,
    padding,
    preserveExtension,
    previewItems,
    hasDuplicates,
    canSubmit,
    isSubmitting,
    formErrorMessage,
    setPatternToken,
    submit,
    close,
  };
}
