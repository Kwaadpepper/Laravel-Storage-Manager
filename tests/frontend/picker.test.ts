import { beforeEach, describe, expect, it, mock } from 'bun:test';
import { createPinia, setActivePinia } from 'pinia';
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
      expect(store.isFileAllowed({ name: 'recording.wav', extension: 'wav' })).toBe(true);
      expect(store.isFileAllowed({ name: 'photo.jpg', extension: 'jpg' })).toBe(false);
      expect(store.isFileAllowed({ name: 'movie.mp4', extension: 'mp4' })).toBe(false);
    });

    it('filters files based on video scopedType', () => {
      const store = usePickerStore();
      store.init('?picker=1&type=video', fakeOpener);

      expect(store.isFileAllowed({ name: 'video.mp4', extension: 'mp4' })).toBe(true);
      expect(store.isFileAllowed({ name: 'clip.webm', extension: 'webm' })).toBe(true);
      expect(store.isFileAllowed({ name: 'sound.mp3', extension: 'mp3' })).toBe(false);
    });

    it('filters files based on pdf scopedType', () => {
      const store = usePickerStore();
      store.init('?picker=1&type=pdf', fakeOpener);

      expect(store.isFileAllowed({ name: 'contract.pdf', extension: 'pdf' })).toBe(true);
      expect(store.isFileAllowed({ name: 'contract.docx', extension: 'docx' })).toBe(false);
    });

    it('filters files based on archive scopedType', () => {
      const store = usePickerStore();
      store.init('?picker=1&type=archive', fakeOpener);

      expect(store.isFileAllowed({ name: 'bundle.zip', extension: 'zip' })).toBe(true);
      expect(store.isFileAllowed({ name: 'bundle.tar', extension: 'tar' })).toBe(true);
      expect(store.isFileAllowed({ name: 'photo.jpg', extension: 'jpg' })).toBe(false);
    });
  });

  describe('Selection Capability (canSelectFile)', () => {
    const validImageFile: TreeNodeFile = {
      path: '/images/cat.jpg',
      name: 'cat.jpg',
      size: 1024,
      extension: 'jpg',
      visibility: 'public',
      publicUrl: '/storage/images/cat.jpg',
    };

    const fileWithoutUrl: TreeNodeFile = {
      path: '/images/cat.jpg',
      name: 'cat.jpg',
      size: 1024,
      extension: 'jpg',
      visibility: 'public',
      publicUrl: null,
    };

    const directoryNode: TreeNodeDirectory = {
      path: '/images',
      name: 'images',
      visibility: 'public',
    };

    it('returns false if not in picker mode', () => {
      const store = usePickerStore();
      store.init('', null);

      expect(store.canSelectFile(validImageFile)).toBe(false);
    });

    it('returns false if node is a directory or null', () => {
      const store = usePickerStore();
      store.init('?picker=1', fakeOpener);

      expect(store.canSelectFile(directoryNode)).toBe(false);
      expect(store.canSelectFile(null)).toBe(false);
      expect(store.canSelectFile(undefined)).toBe(false);
    });

    it('returns false if file has no publicUrl', () => {
      const store = usePickerStore();
      store.init('?picker=1', fakeOpener);

      expect(store.canSelectFile(fileWithoutUrl)).toBe(false);
    });

    it('returns false if file does not match scopedType', () => {
      const store = usePickerStore();
      store.init('?picker=1&type=video', fakeOpener);

      expect(store.canSelectFile(validImageFile)).toBe(false);
    });

    it('returns true when in picker mode, publicUrl is present, and matches scopedType', () => {
      const store = usePickerStore();
      store.init('?picker=1&type=image', fakeOpener);

      expect(store.canSelectFile(validImageFile)).toBe(true);
    });
  });

  describe('File Selection & postMessage (selectFile)', () => {
    it('sends resolved absolute URL to opener and closes window', () => {
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
        },
        'http://localhost:8000'
      );
      expect(closeMock).toHaveBeenCalledTimes(1);

      restoreGlobals();
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
});
