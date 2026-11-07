import { TreeNodeDirectory, TreeNodeFile } from '@ts/types';
import { getFileTypeInfo } from '@ts/utils/file-type';
import { defineStore } from 'pinia';
import { ref } from 'vue';

export type ScopedType = 'image' | 'audio' | 'video' | 'pdf' | 'archive';

export const VALID_SCOPED_TYPES: readonly ScopedType[] = [
  'image',
  'audio',
  'video',
  'pdf',
  'archive',
];

export const usePickerStore = defineStore('picker', () => {
  const isPickerMode = ref(false);
  const sender = ref('storage-manager');
  const scopedType = ref<ScopedType | null>(null);

  function init(searchParamsString?: string, openerObj?: unknown): void {
    const opener = openerObj !== undefined ? openerObj : (typeof globalThis !== 'undefined' ? globalThis.opener : null);
    const search = searchParamsString !== undefined
      ? searchParamsString
      : (typeof globalThis !== 'undefined' && globalThis.location ? globalThis.location.search : '');
    const params = new URLSearchParams(search);

    const hasPicker = params.has('picker') && params.get('picker') !== '0' && params.get('picker') !== 'false';
    const senderParam = params.get('sender');
    const hasSender = senderParam !== null && senderParam.trim() !== '';

    isPickerMode.value = Boolean(opener) && (hasPicker || hasSender);
    sender.value = hasSender ? senderParam!.trim() : 'storage-manager';

    const typeParam = params.get('type')?.toLowerCase();
    scopedType.value = VALID_SCOPED_TYPES.includes(typeParam as ScopedType)
      ? (typeParam as ScopedType)
      : null;
  }

  // Initialize from current globalThis environment
  init();

  function isFileAllowed(file: { name: string; extension?: string | null }): boolean {
    if (!scopedType.value) {
      return true;
    }
    const typeInfo = getFileTypeInfo(file.name, file.extension ?? null);
    return typeInfo.category === scopedType.value;
  }

  function canSelectFile(file?: TreeNodeFile | TreeNodeDirectory | null): boolean {
    if (!file || !('extension' in file)) {
      return false;
    }
    return isPickerMode.value && Boolean(file.publicUrl) && isFileAllowed(file);
  }

  function selectFile(file: TreeNodeFile): void {
    if (!canSelectFile(file) || !globalThis.opener || !file.publicUrl) {
      return;
    }

    const origin = (typeof globalThis !== 'undefined' && globalThis.location?.origin)
      ? globalThis.location.origin
      : 'http://localhost';

    const resolvedUrl = new URL(file.publicUrl, origin).toString();

    globalThis.opener.postMessage(
      { sender: sender.value, message: resolvedUrl },
      origin
    );

    if (typeof globalThis.close === 'function') {
      globalThis.close();
    }
  }

  return {
    isPickerMode,
    sender,
    scopedType,
    init,
    isFileAllowed,
    canSelectFile,
    selectFile,
  };
});

export type PickerStore = ReturnType<typeof usePickerStore>;
