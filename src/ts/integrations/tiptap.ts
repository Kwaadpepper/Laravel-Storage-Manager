import { PickerFilePayload, ScopedType } from '../stores/picker-store';
import { isImageFile } from './ckeditor5';
import { StorageManagerPicker } from './standalone';

export interface TiptapChain {
  focus: () => TiptapChain;
  setImage: (options: { src: string; alt?: string; title?: string }) => TiptapChain;
  setLink: (options: { href: string; target?: string }) => TiptapChain;
  insertContent: (content: string | Record<string, unknown>) => TiptapChain;
  run: () => boolean;
}

export interface TiptapEditor {
  chain: () => TiptapChain;
  commands: {
    setImage?: (options: { src: string; alt?: string; title?: string }) => boolean;
    setLink?: (options: { href: string }) => boolean;
    insertContent?: (content: string | Record<string, unknown>) => boolean;
  };
}

export interface StorageManagerTiptapOptions {
  url?: string;
  sender?: string;
  type?: ScopedType;
  mode?: 'popup' | 'modal';
  title?: string;
}

export function insertFileIntoTiptap(editor: TiptapEditor, file: PickerFilePayload): void {
  if (isImageFile(file)) {
    editor
      .chain()
      .focus()
      .setImage({
        src: file.url,
        alt: file.name,
        title: file.name,
      })
      .run();
    return;
  }

  // Non-image file: insert as linked text
  editor
    .chain()
    .focus()
    .setLink({ href: file.url })
    .insertContent(file.name)
    .run();
}

export function openStorageManagerTiptap(
  editor: TiptapEditor,
  options?: Partial<StorageManagerTiptapOptions>
): StorageManagerPicker {
  const url = options?.url ?? '/storage-manager';
  const sender = options?.sender ?? 'storage-manager-tiptap';
  const type = options?.type ?? 'image';
  const mode = options?.mode ?? 'popup';

  return StorageManagerPicker.open({
    url,
    sender,
    type,
    mode,
    title: options?.title ?? 'Storage Manager - Tiptap',
    onSelect: (file) => {
      insertFileIntoTiptap(editor, file);
    },
  });
}

/**
 * Tiptap Extension definition compatible with Tiptap v2 / v3
 */
export const StorageManagerTiptap = {
  name: 'storageManager',

  addOptions(): StorageManagerTiptapOptions {
    return {
      url: '/storage-manager',
      sender: 'storage-manager-tiptap',
      type: 'image',
      mode: 'popup',
    };
  },

  create(options?: Partial<StorageManagerTiptapOptions>) {
    return {
      ...this,
      options: {
        ...this.addOptions(),
        ...options,
      },
    };
  },
};

// Global browser window attachment
if (typeof window !== 'undefined') {
  (window as unknown as {
    StorageManagerTiptap: typeof StorageManagerTiptap;
    openStorageManagerTiptap: typeof openStorageManagerTiptap;
  }).StorageManagerTiptap = StorageManagerTiptap;

  (window as unknown as {
    openStorageManagerTiptap: typeof openStorageManagerTiptap;
  }).openStorageManagerTiptap = openStorageManagerTiptap;
}
