import { PickerFilePayload, ScopedType } from '../stores/picker-store';
import { StorageManagerPicker } from './standalone';

export interface CKEditor5Config {
  url?: string;
  sender?: string;
  type?: ScopedType;
  mode?: 'popup' | 'modal';
  title?: string;
}

export interface CKEditor5Editor {
  config: {
    get: (key: string) => CKEditor5Config | undefined;
  };
  commands: {
    get: (name: string) => { execute: (args?: unknown) => void } | undefined;
  };
  execute: (commandName: string, commandParams?: unknown) => void;
  ui: {
    componentFactory: {
      add: (name: string, callback: (locale: unknown) => unknown) => void;
    };
  };
  model: {
    change: (callback: (writer: CKEditor5Writer) => void) => void;
    document: {
      selection: unknown;
    };
    insertContent: (content: unknown, selectable?: unknown) => void;
    schema: {
      isRegistered: (name: string) => boolean;
    };
  };
}

export interface CKEditor5Writer {
  createElement: (name: string, attributes?: Record<string, unknown>) => unknown;
  createText: (text: string, attributes?: Record<string, unknown>) => unknown;
}

export interface CKEditor5ButtonView {
  set: (options: { label: string; icon?: string; tooltip: boolean }) => void;
  on: (event: string, callback: () => void) => void;
}

const IMAGE_ICON_SVG =
  '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>';

export function isImageFile(file: PickerFilePayload): boolean {
  if (file.mimeType?.startsWith('image/')) {
    return true;
  }
  const ext = file.extension?.toLowerCase() ?? '';
  return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif', 'bmp'].includes(ext);
}

export function insertFileIntoCKEditor5(editor: CKEditor5Editor, file: PickerFilePayload): void {
  if (isImageFile(file)) {
    try {
      editor.execute('insertImage', { source: file.url, alt: file.name });
      return;
    } catch {
      // Fallback to manual model change if command is unavailable
    }

    editor.model.change((writer) => {
      const elementName = editor.model.schema.isRegistered('imageBlock') ? 'imageBlock' : 'imageInline';
      const imageElement = writer.createElement(elementName, {
        src: file.url,
        alt: file.name,
      });
      editor.model.insertContent(imageElement, editor.model.document.selection);
    });
    return;
  }

  // Non-image file: insert as linked text
  editor.model.change((writer) => {
    const linkedText = writer.createText(file.name, {
      linkHref: file.url,
    });
    editor.model.insertContent(linkedText, editor.model.document.selection);
  });
}

export class StorageManagerCKEditor5 {
  public static readonly pluginName = 'StorageManager';

  constructor(public readonly editor: CKEditor5Editor) {}

  public init(): void {
    const editor = this.editor;
    const config = editor.config.get('storageManager') ?? {};
    const url = config.url ?? '/storage-manager';
    const type = config.type ?? 'image';
    const mode = config.mode ?? 'popup';

    editor.ui.componentFactory.add('storageManager', () => {
      // Create lightweight button object matching CKEditor 5 ButtonView interface
      const button: CKEditor5ButtonView = {
        set({ label, icon, tooltip }) {
          Object.assign(this, { label, icon, tooltip });
        },
        on(event, callback) {
          if (event === 'execute') {
            (this as unknown as { _execute: () => void })._execute = callback;
          }
        },
      };

      button.set({
        label: 'Insert Media from Storage Manager',
        icon: IMAGE_ICON_SVG,
        tooltip: true,
      });

      button.on('execute', () => {
        StorageManagerPicker.open({
          url,
          sender: config.sender ?? 'storage-manager-ckeditor5',
          type,
          mode,
          title: config.title ?? 'Storage Manager - CKEditor 5',
          onSelect: (file) => {
            insertFileIntoCKEditor5(editor, file);
          },
        });
      });

      return button;
    });
  }
}

// Global browser window attachment
if (typeof window !== 'undefined') {
  (window as unknown as { StorageManagerCKEditor5: typeof StorageManagerCKEditor5 }).StorageManagerCKEditor5 =
    StorageManagerCKEditor5;
}
