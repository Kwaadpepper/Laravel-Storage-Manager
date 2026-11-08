import { beforeEach, describe, expect, it, mock } from 'bun:test';
import { createPinia, setActivePinia } from 'pinia';
import {
  insertFileIntoCKEditor5,
  isImageFile,
  StorageManagerCKEditor5,
  type CKEditor5Editor,
} from '../../src/ts/integrations/ckeditor5';
import { StorageManagerPicker } from '../../src/ts/integrations/standalone';
import {
  insertFileIntoTiptap,
  openStorageManagerTiptap,
  StorageManagerTiptap,
  type TiptapEditor,
} from '../../src/ts/integrations/tiptap';
import { usePickerStore } from '../../src/ts/stores/picker-store';
import type { TreeNodeDirectory, TreeNodeFile } from '../../src/ts/types';

describe('PickerStore', () => {
  let fakeOpener: { postMessage: ReturnType<typeof mock> };
  let originalLocation: typeof globalThis.location;
  let originalOpener: typeof globalThis.opener;
  let originalClose: typeof globalThis.close;

  beforeEach(() => {
    setActivePinia(createPinia());

    fakeOpener = {
      postMessage: mock(() => {}),
    };

    originalLocation = globalThis.location;
    originalOpener = globalThis.opener;
    originalClose = globalThis.close;

    globalThis.location = {
      origin: 'http://localhost:8000',
      search: '',
    } as unknown as Location;

    globalThis.opener = null;
    globalThis.close = mock(() => {}) as unknown as typeof globalThis.close;
  });

  const restoreGlobals = () => {
    globalThis.location = originalLocation;
    globalThis.opener = originalOpener;
    globalThis.close = originalClose;
  };

  describe('Picker Mode Detection', () => {
    it('is disabled by default when opener is null', () => {
      globalThis.opener = null;
      globalThis.location = {
        origin: 'http://localhost:8000',
        search: '?picker=1',
      } as unknown as Location;

      const store = usePickerStore();
      store.init();

      expect(store.isPickerMode).toBe(false);
      expect(store.sender).toBe('storage-manager');
      expect(store.scopedType).toBeNull();
      restoreGlobals();
    });

    it('is enabled when opener exists and picker=1', () => {
      globalThis.opener = fakeOpener as unknown as Window;
      globalThis.location = {
        origin: 'http://localhost:8000',
        search: '?picker=1',
      } as unknown as Location;

      const store = usePickerStore();
      store.init();

      expect(store.isPickerMode).toBe(true);
      expect(store.sender).toBe('storage-manager');
      restoreGlobals();
    });

    it('resolves custom sender when provided in search params', () => {
      globalThis.opener = fakeOpener as unknown as Window;
      globalThis.location = {
        origin: 'http://localhost:8000',
        search: '?sender=custom-app',
      } as unknown as Location;

      const store = usePickerStore();
      store.init();

      expect(store.isPickerMode).toBe(true);
      expect(store.sender).toBe('custom-app');
      restoreGlobals();
    });

    it('remains disabled when opener exists but neither picker nor sender is present', () => {
      globalThis.opener = fakeOpener as unknown as Window;
      globalThis.location = {
        origin: 'http://localhost:8000',
        search: '',
      } as unknown as Location;

      const store = usePickerStore();
      store.init();

      expect(store.isPickerMode).toBe(false);
      restoreGlobals();
    });

    it('resolves valid scoped types correctly', () => {
      const store = usePickerStore();

      store.init('?picker=1&type=image', fakeOpener);
      expect(store.scopedType).toBe('image');

      store.init('?picker=1&type=AUDIO', fakeOpener);
      expect(store.scopedType).toBe('audio');

      store.init('?picker=1&type=video', fakeOpener);
      expect(store.scopedType).toBe('video');

      store.init('?picker=1&type=pdf', fakeOpener);
      expect(store.scopedType).toBe('pdf');

      store.init('?picker=1&type=archive', fakeOpener);
      expect(store.scopedType).toBe('archive');

      store.init('?picker=1&type=unknown_type', fakeOpener);
      expect(store.scopedType).toBeNull();

      store.init('?picker=1', fakeOpener);
      expect(store.scopedType).toBeNull();

      restoreGlobals();
    });
  });

  describe('File Type Scoping (isFileAllowed)', () => {
    it('allows all files when scopedType is null', () => {
      const store = usePickerStore();
      store.init('?picker=1', fakeOpener);

      expect(store.isFileAllowed({ name: 'photo.jpg', extension: 'jpg' })).toBe(true);
      expect(store.isFileAllowed({ name: 'track.mp3', extension: 'mp3' })).toBe(true);
      expect(store.isFileAllowed({ name: 'doc.pdf', extension: 'pdf' })).toBe(true);
      expect(store.isFileAllowed({ name: 'bundle.zip', extension: 'zip' })).toBe(true);
      expect(store.isFileAllowed({ name: 'notes.txt', extension: 'txt' })).toBe(true);
    });

    it('filters files based on image scopedType', () => {
      const store = usePickerStore();
      store.init('?picker=1&type=image', fakeOpener);

      expect(store.isFileAllowed({ name: 'banner.png', extension: 'png' })).toBe(true);
      expect(store.isFileAllowed({ name: 'photo.jpg', extension: 'jpg' })).toBe(true);
      expect(store.isFileAllowed({ name: 'logo.svg', extension: 'svg' })).toBe(true);
      expect(store.isFileAllowed({ name: 'track.mp3', extension: 'mp3' })).toBe(false);
      expect(store.isFileAllowed({ name: 'document.pdf', extension: 'pdf' })).toBe(false);
      expect(store.isFileAllowed({ name: 'archive.zip', extension: 'zip' })).toBe(false);
    });

    it('filters files based on audio scopedType', () => {
      const store = usePickerStore();
      store.init('?picker=1&type=audio', fakeOpener);

      expect(store.isFileAllowed({ name: 'song.mp3', extension: 'mp3' })).toBe(true);
      expect(store.isFileAllowed({ name: 'voice.wav', extension: 'wav' })).toBe(true);
      expect(store.isFileAllowed({ name: 'photo.jpg', extension: 'jpg' })).toBe(false);
    });

    it('filters files based on video scopedType', () => {
      const store = usePickerStore();
      store.init('?picker=1&type=video', fakeOpener);

      expect(store.isFileAllowed({ name: 'clip.mp4', extension: 'mp4' })).toBe(true);
      expect(store.isFileAllowed({ name: 'movie.mkv', extension: 'mkv' })).toBe(true);
      expect(store.isFileAllowed({ name: 'song.mp3', extension: 'mp3' })).toBe(false);
    });

    it('filters files based on pdf scopedType', () => {
      const store = usePickerStore();
      store.init('?picker=1&type=pdf', fakeOpener);

      expect(store.isFileAllowed({ name: 'invoice.pdf', extension: 'pdf' })).toBe(true);
      expect(store.isFileAllowed({ name: 'notes.txt', extension: 'txt' })).toBe(false);
    });

    it('filters files based on archive scopedType', () => {
      const store = usePickerStore();
      store.init('?picker=1&type=archive', fakeOpener);

      expect(store.isFileAllowed({ name: 'files.zip', extension: 'zip' })).toBe(true);
      expect(store.isFileAllowed({ name: 'backup.tar.gz', extension: 'gz' })).toBe(true);
      expect(store.isFileAllowed({ name: 'app.rar', extension: 'rar' })).toBe(true);
      expect(store.isFileAllowed({ name: 'photo.jpg', extension: 'jpg' })).toBe(false);
    });
  });

  describe('Selection Capability (canSelectFile)', () => {
    it('returns false if not in picker mode', () => {
      const store = usePickerStore();
      const file: TreeNodeFile = {
        path: '/photos/cat.png',
        name: 'cat.png',
        size: 1024,
        extension: 'png',
        visibility: 'public',
        publicUrl: 'http://localhost/photos/cat.png',
      };

      expect(store.canSelectFile(file)).toBe(false);
    });

    it('returns false if node is a directory or null', () => {
      const store = usePickerStore();
      store.init('?picker=1', fakeOpener);

      const dir: TreeNodeDirectory = {
        path: '/photos',
        name: 'photos',
        hasSubdirectories: false,
      };

      expect(store.canSelectFile(null)).toBe(false);
      expect(store.canSelectFile(dir)).toBe(false);
    });

    it('returns false if file has no publicUrl', () => {
      const store = usePickerStore();
      store.init('?picker=1', fakeOpener);

      const file: TreeNodeFile = {
        path: '/photos/cat.png',
        name: 'cat.png',
        size: 1024,
        extension: 'png',
        visibility: 'public',
        publicUrl: null,
      };

      expect(store.canSelectFile(file)).toBe(false);
    });

    it('returns false if file does not match scopedType', () => {
      const store = usePickerStore();
      store.init('?picker=1&type=video', fakeOpener);

      const file: TreeNodeFile = {
        path: '/photos/cat.png',
        name: 'cat.png',
        size: 1024,
        extension: 'png',
        visibility: 'public',
        publicUrl: 'http://localhost/photos/cat.png',
      };

      expect(store.canSelectFile(file)).toBe(false);
    });

    it('returns true when in picker mode, publicUrl is present, and matches scopedType', () => {
      const store = usePickerStore();
      store.init('?picker=1&type=image', fakeOpener);

      const file: TreeNodeFile = {
        path: '/photos/cat.png',
        name: 'cat.png',
        size: 1024,
        extension: 'png',
        visibility: 'public',
        publicUrl: 'http://localhost/photos/cat.png',
      };

      expect(store.canSelectFile(file)).toBe(true);
    });
  });

  describe('File Selection & postMessage (selectFile)', () => {
    it('sends structured payload to opener and closes window', () => {
      const closeMock = mock(() => {});
      globalThis.close = closeMock as unknown as typeof globalThis.close;
      globalThis.opener = fakeOpener as unknown as Window;
      globalThis.location = {
        origin: 'http://localhost:8000',
        search: '?picker=1&sender=my-cms',
      } as unknown as Location;

      const store = usePickerStore();
      store.init();

      const file: TreeNodeFile = {
        path: '/uploads/avatar.png',
        name: 'avatar.png',
        size: 2048,
        extension: 'png',
        visibility: 'public',
        publicUrl: '/storage/uploads/avatar.png',
      };

      store.selectFile(file);

      expect(fakeOpener.postMessage).toHaveBeenCalledTimes(1);
      expect(fakeOpener.postMessage).toHaveBeenCalledWith(
        {
          sender: 'my-cms',
          message: 'http://localhost:8000/storage/uploads/avatar.png',
          file: {
            url: 'http://localhost:8000/storage/uploads/avatar.png',
            name: 'avatar.png',
            path: '/uploads/avatar.png',
            size: 2048,
            extension: 'png',
            mimeType: null,
          },
        },
        'http://localhost:8000'
      );
      expect(closeMock).toHaveBeenCalledTimes(1);

      restoreGlobals();
    });

    it('preserves already absolute URLs', () => {
      const closeMock = mock(() => {});
      globalThis.close = closeMock as unknown as typeof globalThis.close;
      globalThis.opener = fakeOpener as unknown as Window;
      globalThis.location = {
        origin: 'http://localhost:8000',
        search: '?picker=1',
      } as unknown as Location;

      const store = usePickerStore();
      store.init();

      const file: TreeNodeFile = {
        path: '/uploads/s3-file.png',
        name: 's3-file.png',
        size: 4096,
        extension: 'png',
        visibility: 'public',
        publicUrl: 'https://bucket.s3.amazonaws.com/s3-file.png',
      };

      store.selectFile(file);

      expect(fakeOpener.postMessage).toHaveBeenCalledWith(
        {
          sender: 'storage-manager',
          message: 'https://bucket.s3.amazonaws.com/s3-file.png',
          file: {
            url: 'https://bucket.s3.amazonaws.com/s3-file.png',
            name: 's3-file.png',
            path: '/uploads/s3-file.png',
            size: 4096,
            extension: 'png',
            mimeType: null,
          },
        },
        'http://localhost:8000'
      );
      expect(closeMock).toHaveBeenCalledTimes(1);

      restoreGlobals();
    });

    it('falls back to iframe parent when opener is null', () => {
      const fakeParent = { postMessage: mock(() => {}) };
      const store = usePickerStore();
      store.init('?picker=1', fakeParent);

      const file: TreeNodeFile = {
        path: '/uploads/modal.png',
        name: 'modal.png',
        size: 1024,
        extension: 'png',
        visibility: 'public',
        publicUrl: '/storage/modal.png',
      };

      store.selectFile(file);

      expect(fakeParent.postMessage).toHaveBeenCalledTimes(1);
      expect(fakeParent.postMessage).toHaveBeenCalledWith(
        {
          sender: 'storage-manager',
          message: 'http://localhost:8000/storage/modal.png',
          file: {
            url: 'http://localhost:8000/storage/modal.png',
            name: 'modal.png',
            path: '/uploads/modal.png',
            size: 1024,
            extension: 'png',
            mimeType: null,
          },
        },
        'http://localhost:8000'
      );
    });

    it('does nothing when file cannot be selected', () => {
      const closeMock = mock(() => {});
      globalThis.close = closeMock as unknown as typeof globalThis.close;
      globalThis.opener = fakeOpener as unknown as Window;
      globalThis.location = {
        origin: 'http://localhost:8000',
        search: '?picker=1&type=video',
      } as unknown as Location;

      const store = usePickerStore();
      store.init();

      const fileWithoutUrl: TreeNodeFile = {
        path: '/images/cat.jpg',
        name: 'cat.jpg',
        size: 1024,
        extension: 'jpg',
        visibility: 'public',
        publicUrl: null,
      };

      store.selectFile(fileWithoutUrl);

      expect(fakeOpener.postMessage).not.toHaveBeenCalled();
      expect(closeMock).not.toHaveBeenCalled();

      restoreGlobals();
    });
  });

  describe('Integrations', () => {
    describe('CKEditor 5 Integration', () => {
      it('correctly identifies image files vs non-image files', () => {
        expect(isImageFile({ url: '', name: 'test.png', path: '', size: 0, extension: 'png' })).toBe(true);
        expect(isImageFile({ url: '', name: 'test.jpg', path: '', size: 0, extension: 'jpg' })).toBe(true);
        expect(isImageFile({ url: '', name: 'test.svg', path: '', size: 0, extension: 'svg' })).toBe(true);
        expect(isImageFile({ url: '', name: 'test.pdf', path: '', size: 0, extension: 'pdf' })).toBe(false);
        expect(isImageFile({ url: '', name: 'test.zip', path: '', size: 0, extension: 'zip' })).toBe(false);
      });

      it('inserts image via editor.execute insertImage when available', () => {
        const executeMock = mock(() => {});
        const fakeEditor = {
          config: { get: () => undefined },
          commands: { get: () => undefined },
          execute: executeMock,
          ui: { componentFactory: { add: () => {} } },
          model: {
            change: mock(() => {}),
            document: { selection: {} },
            insertContent: mock(() => {}),
            schema: { isRegistered: () => true },
          },
        } as unknown as CKEditor5Editor;

        insertFileIntoCKEditor5(fakeEditor, {
          url: 'http://localhost/image.png',
          name: 'image.png',
          path: '/image.png',
          size: 1234,
          extension: 'png',
        });

        expect(executeMock).toHaveBeenCalledWith('insertImage', {
          source: 'http://localhost/image.png',
          alt: 'image.png',
        });
      });

      it('inserts linked text for non-image file into CKEditor 5 model', () => {
        const createElementMock = mock(() => ({}));
        const createTextMock = mock((text: string, attrs?: Record<string, unknown>) => ({ text, attrs }));
        const insertContentMock = mock(() => {});

        const fakeEditor = {
          config: { get: () => undefined },
          commands: { get: () => undefined },
          execute: mock(() => {}),
          ui: { componentFactory: { add: () => {} } },
          model: {
            change: (callback: (writer: unknown) => void) => {
              callback({
                createElement: createElementMock,
                createText: createTextMock,
              });
            },
            document: { selection: {} },
            insertContent: insertContentMock,
            schema: { isRegistered: () => true },
          },
        } as unknown as CKEditor5Editor;

        insertFileIntoCKEditor5(fakeEditor, {
          url: 'http://localhost/doc.pdf',
          name: 'doc.pdf',
          path: '/doc.pdf',
          size: 5000,
          extension: 'pdf',
        });

        expect(createTextMock).toHaveBeenCalledWith('doc.pdf', {
          linkHref: 'http://localhost/doc.pdf',
        });
        expect(insertContentMock).toHaveBeenCalled();
      });

      it('registers storageManager component in CKEditor 5 UI', () => {
        let registeredFactory: (() => unknown) | null = null;
        const fakeEditor = {
          config: {
            get: () => ({ url: '/custom-manager', type: 'image' }),
          },
          commands: { get: () => undefined },
          execute: mock(() => {}),
          ui: {
            componentFactory: {
              add: (name: string, callback: () => unknown) => {
                if (name === 'storageManager') {
                  registeredFactory = callback;
                }
              },
            },
          },
          model: {
            change: mock(() => {}),
            document: { selection: {} },
            insertContent: mock(() => {}),
            schema: { isRegistered: () => true },
          },
        } as unknown as CKEditor5Editor;

        const plugin = new StorageManagerCKEditor5(fakeEditor);
        plugin.init();

        expect(registeredFactory).not.toBeNull();
        const button = (registeredFactory as unknown as () => { label: string; icon: string })?.();
        expect(button.label).toBe('Insert Media from Storage Manager');
      });
    });

    describe('Tiptap Integration', () => {
      it('inserts image using editor chain in Tiptap', () => {
        const runMock = mock(() => true);
        const setImageMock = mock(() => chain);
        const focusMock = mock(() => chain);

        const chain = {
          focus: focusMock,
          setImage: setImageMock,
          setLink: mock(() => chain),
          insertContent: mock(() => chain),
          run: runMock,
        };

        const fakeEditor = {
          chain: () => chain,
          commands: {},
        } as unknown as TiptapEditor;

        insertFileIntoTiptap(fakeEditor, {
          url: 'http://localhost/photo.jpg',
          name: 'photo.jpg',
          path: '/photo.jpg',
          size: 2000,
          extension: 'jpg',
        });

        expect(focusMock).toHaveBeenCalled();
        expect(setImageMock).toHaveBeenCalledWith({
          src: 'http://localhost/photo.jpg',
          alt: 'photo.jpg',
          title: 'photo.jpg',
        });
        expect(runMock).toHaveBeenCalled();
      });

      it('inserts link using editor chain for non-image files in Tiptap', () => {
        const runMock = mock(() => true);
        const setLinkMock = mock(() => chain);
        const insertContentMock = mock(() => chain);
        const focusMock = mock(() => chain);

        const chain = {
          focus: focusMock,
          setImage: mock(() => chain),
          setLink: setLinkMock,
          insertContent: insertContentMock,
          run: runMock,
        };

        const fakeEditor = {
          chain: () => chain,
          commands: {},
        } as unknown as TiptapEditor;

        insertFileIntoTiptap(fakeEditor, {
          url: 'http://localhost/manual.pdf',
          name: 'manual.pdf',
          path: '/manual.pdf',
          size: 15000,
          extension: 'pdf',
        });

        expect(focusMock).toHaveBeenCalled();
        expect(setLinkMock).toHaveBeenCalledWith({ href: 'http://localhost/manual.pdf' });
        expect(insertContentMock).toHaveBeenCalledWith('manual.pdf');
        expect(runMock).toHaveBeenCalled();
      });

      it('provides openStorageManagerTiptap and StorageManagerTiptap extension definition', () => {
        expect(StorageManagerTiptap.name).toBe('storageManager');
        const ext = StorageManagerTiptap.create({ url: '/custom-url' });
        expect(ext.options.url).toBe('/custom-url');
      });
    });

    describe('Standalone Picker', () => {
      it('opens popup window with correct query params', () => {
        const openMock = mock(() => ({ focus: mock(() => {}) }));
        globalThis.open = openMock as unknown as typeof globalThis.open;

        const picker = StorageManagerPicker.open({
          url: '/storage-manager',
          sender: 'test-standalone',
          type: 'image',
          onSelect: () => {},
        });

        expect(openMock).toHaveBeenCalled();
        const calledUrl = openMock.mock.calls[0]?.[0] as string;
        expect(calledUrl).toContain('picker=1');
        expect(calledUrl).toContain('sender=test-standalone');
        expect(calledUrl).toContain('type=image');

        picker.close();
      });
    });
  });
});
