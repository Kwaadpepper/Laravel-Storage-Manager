import { describe, expect, it } from 'bun:test';
import { createPinia, setActivePinia } from 'pinia';
import {
  toAnchorName,
  useClipboardStore,
  useContextualMenuStore,
  useToastStore,
  useUiStore,
  isValidTheme,
  AVAILABLE_THEMES,
} from '../../src/ts/stores';
import { handleApiError } from '../../src/ts/utils';
import { ValidationError } from '../../src/ts/errors';
import type { Path } from '../../src/ts/types';

describe('useToastStore', () => {
  it('adds a toast and assigns an id', () => {
    setActivePinia(createPinia());
    const toastStore = useToastStore();

    const toast = toastStore.pushToast({
      message: 'File uploaded',
      type: 'success',
    });

    expect(toastStore.toasts).toHaveLength(1);
    expect(toastStore.toasts[0].id).toBe(toast.id);
    expect(toastStore.toasts[0].message).toBe('File uploaded');
    expect(toastStore.toasts[0].type).toBe('success');
  });

  it('pulls toast by id', () => {
    setActivePinia(createPinia());
    const toastStore = useToastStore();

    const t1 = toastStore.push({ message: 'First', type: 'info' });
    const t2 = toastStore.push({ message: 'Second', type: 'error' });
    expect(toastStore.toasts).toHaveLength(2);

    toastStore.pullToast(t1.id);
    expect(toastStore.toasts).toHaveLength(1);
    expect(toastStore.toasts[0].id).toBe(t2.id);
  });

  it('auto-dismisses non-persistent toasts with custom timeout', async () => {
    setActivePinia(createPinia());
    const toastStore = useToastStore();

    const toast = toastStore.pushToast(
      { message: 'Transient', type: 'info' },
      { timeout: 20 }
    );

    expect(toastStore.toasts).toHaveLength(1);
    await new Promise((r) => setTimeout(r, 35));
    expect(toastStore.toasts.find((t) => t.id === toast.id)).toBeUndefined();
  });
});

describe('useClipboardStore', () => {
  it('adds and retrieves entries', () => {
    setActivePinia(createPinia());
    const clipboardStore = useClipboardStore();

    expect(clipboardStore.hasEntries).toBe(false);

    clipboardStore.addEntry('/images/photo1.jpg' as Path, '/images/photo2.jpg' as Path);
    expect(clipboardStore.hasEntries).toBe(true);

    const last = clipboardStore.getLastEntry();
    expect(last).toEqual(['/images/photo1.jpg' as Path, '/images/photo2.jpg' as Path]);
  });

  it('clears entries on getEntries when in cut mode', () => {
    setActivePinia(createPinia());
    const clipboardStore = useClipboardStore();

    clipboardStore.setConsumingMode(true);
    expect(clipboardStore.getIsConsumingMode()).toBe(true);

    clipboardStore.addEntry('/item.txt' as Path);
    const retrieved = clipboardStore.getEntries();
    expect(retrieved).toEqual([['/item.txt' as Path]]);

    // Should be cleared because isCutMode was true
    expect(clipboardStore.hasEntries).toBe(false);
    expect(clipboardStore.isCutMode).toBe(false);
  });

  it('removes the last entry and manages history', () => {
    setActivePinia(createPinia());
    const clipboardStore = useClipboardStore();

    clipboardStore.addEntry('/a.txt' as Path);
    clipboardStore.addEntry('/b.txt' as Path);
    expect(clipboardStore.history).toHaveLength(2);

    clipboardStore.removeLastEntry();
    expect(clipboardStore.history).toHaveLength(1);
    expect(clipboardStore.getLastEntry()).toEqual(['/a.txt' as Path]);

    clipboardStore.clearEntries();
    expect(clipboardStore.hasEntries).toBe(false);
  });
});

describe('useUiStore', () => {
  it('validates theme helpers', () => {
    expect(AVAILABLE_THEMES).toEqual(['auto', 'light', 'dark']);
    expect(isValidTheme('auto')).toBe(true);
    expect(isValidTheme('light')).toBe(true);
    expect(isValidTheme('dark')).toBe(true);
    expect(isValidTheme('synthwave')).toBe(false);
  });

  it('detects system theme via matchMedia mock', () => {
    setActivePinia(createPinia());
    const uiStore = useUiStore();

    (globalThis as any).matchMedia = (query: string) => ({
      matches: query.includes('dark'),
      addEventListener: () => {},
      removeEventListener: () => {},
    });

    expect(uiStore.getSystemTheme()).toBe('dark');
  });
});

describe('useContextualMenuStore', () => {
  it('registers, opens, and closes contextual menus', () => {
    setActivePinia(createPinia());
    const cmStore = useContextualMenuStore();
    const anchor = toAnchorName('/folder/sub');

    let clicked = false;
    cmStore.register(anchor, [
      { label: 'Custom Action', onClick: () => { clicked = true; } },
    ]);

    const opened = cmStore.open(anchor);
    expect(opened).toBe(true);
    expect(cmStore.visible).toBe(true);
    expect(cmStore.entries).toHaveLength(1);
    expect(cmStore.entries[0].label).toBe('Custom Action');

    (cmStore.entries[0] as any).onClick();
    expect(clicked).toBe(true);

    cmStore.close();
    expect(cmStore.visible).toBe(false);

    cmStore.unregister(anchor);
    const reOpened = cmStore.open(anchor);
    expect(reOpened).toBe(false);
  });
});

describe('handleApiError utility', () => {
  it('delegates validation errors to onFieldError callback', () => {
    setActivePinia(createPinia());
    const toastStore = useToastStore();

    const error = new ValidationError(
      { file_name: 'File already exists' },
      'Validation failed'
    );

    let handledField = '';
    let handledMessage = '';

    handleApiError(error, {
      fieldMap: { file_name: 'fileName' },
      onFieldError: (field, message) => {
        handledField = field;
        handledMessage = message;
        return true;
      },
    });

    expect(handledField).toBe('fileName');
    expect(handledMessage).toBe('File already exists');
    // Toast should NOT be pushed because onFieldError handled it
    expect(toastStore.toasts).toHaveLength(0);
  });

  it('pushes fallback toast for generic errors', () => {
    setActivePinia(createPinia());
    const toastStore = useToastStore();

    handleApiError(new Error('Network error'), {
      fallbackMessage: 'Custom error message',
    });

    expect(toastStore.toasts).toHaveLength(1);
    expect(toastStore.toasts[0].message).toBe('Custom error message');
    expect(toastStore.toasts[0].type).toBe('error');
  });
});
