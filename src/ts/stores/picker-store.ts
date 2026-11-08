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

export interface PickerFilePayload {
  url: string;
  name: string;
  path: string;
  size: number;
  extension: string | null;
  mimeType?: string | null;
}

export interface PickerMessageEventData {
  sender: string;
  message: string;
  file: PickerFilePayload;
}

export const usePickerStore = defineStore('picker', () => {
  const isPickerMode = ref(false);
  const sender = ref('storage-manager');
  const scopedType = ref<ScopedType | null>(null);
  const targetWindow = ref<unknown>(null);

  function resolveTargetWindow(customTarget?: unknown): unknown {
    if (customTarget !== undefined) {
      return customTarget;
    }
    if (typeof globalThis === 'undefined') {
      return null;
    }
    if (globalThis.opener) {
      return globalThis.opener;
    }
    if (globalThis.parent && (globalThis.parent as unknown) !== (globalThis as unknown)) {
      return globalThis.parent;
    }
    return null;
  }

  function init(searchParamsString?: string, openerObj?: unknown): void {
    const target = resolveTargetWindow(openerObj);
    targetWindow.value = target;

    const search = searchParamsString !== undefined
      ? searchParamsString
      : (typeof globalThis !== 'undefined' && globalThis.location ? globalThis.location.search : '');
    const params = new URLSearchParams(search);

    const hasPicker = params.has('picker') && params.get('picker') !== '0' && params.get('picker') !== 'false';
    const senderParam = params.get('sender');
    const hasSender = senderParam !== null && senderParam.trim() !== '';
    const hasCkFunc = params.has('CKEditorFuncNum');

    isPickerMode.value = Boolean(target) && (hasPicker || hasSender || hasCkFunc);
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
    const target = (targetWindow.value ?? resolveTargetWindow()) as Window | null;
    if (!canSelectFile(file) || !target || !file.publicUrl) {
      return;
    }

    const origin = (typeof globalThis !== 'undefined' && globalThis.location?.origin)
      ? globalThis.location.origin
      : 'http://localhost';

    const resolvedUrl = new URL(file.publicUrl, origin).toString();

    const filePayload: PickerFilePayload = {
      url: resolvedUrl,
      name: file.name,
      path: file.path,
      size: file.size,
      extension: file.extension ?? null,
      mimeType: (file as { mimeType?: string | null }).mimeType ?? null,
    };

    const payload: PickerMessageEventData = {
      sender: sender.value,
      message: resolvedUrl,
      file: filePayload,
    };

    target.postMessage(payload, origin);

    if (globalThis.opener) {
      const openerObj = globalThis.opener as unknown as {
        fmSetLink?: (url: string, file?: PickerFilePayload) => void;
        CKEDITOR?: {
          tools?: {
            callFunction?: (funcNum: string, url: string) => void;
          };
        };
      };

      if (typeof openerObj.fmSetLink === 'function') {
        try {
          openerObj.fmSetLink(resolvedUrl, filePayload);
        } catch {
          // Cross-origin fallback
        }
      }

      const currentSearch = (typeof globalThis !== 'undefined' && globalThis.location?.search)
        ? globalThis.location.search
        : '';
      const params = new URLSearchParams(currentSearch);
      const ckFuncNum = params.get('CKEditorFuncNum');
      if (ckFuncNum && typeof openerObj.CKEDITOR?.tools?.callFunction === 'function') {
        try {
          openerObj.CKEDITOR.tools.callFunction(ckFuncNum, resolvedUrl);
        } catch {
          // Cross-origin fallback
        }
      }
    }

    if (globalThis.opener && typeof globalThis.close === 'function') {
      globalThis.close();
    }
  }

  return {
    isPickerMode,
    sender,
    scopedType,
    targetWindow,
    init,
    isFileAllowed,
    canSelectFile,
    selectFile,
  };
});

export type PickerStore = ReturnType<typeof usePickerStore>;
