import { Path } from '@ts/types';
import { defineStore } from 'pinia';
import { ref } from 'vue';

export type Theme = 'auto' | 'light' | 'dark';

export const AVAILABLE_THEMES: readonly Theme[] = ['auto', 'light', 'dark'] as const;

export function isValidTheme(theme: string): theme is Theme {
  return (AVAILABLE_THEMES as readonly string[]).includes(theme);
}

export enum ModalState {
  Closed = 'closed',
  Opened = 'opened',
}

const LOCAL_STORAGE_KEY = 'sm-theme';
const VIEW_MODE_KEY = 'sm-view-mode';
const TREE_VISIBLE_KEY = 'sm-tree-visible';

export type ViewMode = 'list' | 'tiles';

function getLocalStorageViewMode(): ViewMode {
  if (typeof localStorage === 'undefined') return 'list';
  const stored = localStorage.getItem(VIEW_MODE_KEY);
  return stored === 'tiles' ? 'tiles' : 'list';
}

function setLocalStorageViewMode(mode: ViewMode): void {
  if (typeof localStorage === 'undefined') return;
  localStorage.setItem(VIEW_MODE_KEY, mode);
}

function getLocalStorageTreeVisible(): boolean {
  if (typeof localStorage === 'undefined') return true;
  return localStorage.getItem(TREE_VISIBLE_KEY) !== 'false';
}

function setLocalStorageTreeVisible(visible: boolean): void {
  if (typeof localStorage === 'undefined') return;
  localStorage.setItem(TREE_VISIBLE_KEY, String(visible));
}

function getLocalStorageTheme(): Theme {
  if (typeof localStorage === 'undefined') return 'auto';
  const storedTheme = localStorage.getItem(LOCAL_STORAGE_KEY);
  return storedTheme === 'light' || storedTheme === 'dark' || storedTheme === 'auto'
    ? storedTheme
    : 'auto';
}

function setLocalStorageTheme(theme: Theme): void {
  if (typeof localStorage === 'undefined') return;
  localStorage.setItem(LOCAL_STORAGE_KEY, theme);
}

export const useUiStore = defineStore('ui', () => {
  const theme = ref<Theme>(getLocalStorageTheme());
  const aboutModal = ref<ModalState>(ModalState.Closed);
  const newDirectoryModal = ref<ModalState>(ModalState.Closed);
  const renameDirectoryModal = ref<ModalState>(ModalState.Closed);
  const deleteModal = ref<ModalState>(ModalState.Closed);
  const uploadFileModal = ref<ModalState>(ModalState.Closed);
  const createFileModal = ref<ModalState>(ModalState.Closed);
  const renameFileModal = ref<ModalState>(ModalState.Closed);
  const viewFileModal = ref<ModalState>(ModalState.Closed);
  const moveModal = ref<ModalState>(ModalState.Closed);
  const copyModal = ref<ModalState>(ModalState.Closed);
  const bulkRenameModal = ref<ModalState>(ModalState.Closed);
  const zipModal = ref<ModalState>(ModalState.Closed);
  const unzipModal = ref<ModalState>(ModalState.Closed);
  const cropImageModal = ref<ModalState>(ModalState.Closed);
  const fileMetadataModal = ref<ModalState>(ModalState.Closed);
  const trashModal = ref<ModalState>(ModalState.Closed);
  const targetDirectoryPath = ref<Path | null>(null);
  const targetFilePath = ref<Path | null>(null);
  const targetArchiveFilePath = ref<Path | null>(null);
  const viewMode = ref<ViewMode>(getLocalStorageViewMode());
  const treeVisible = ref<boolean>(getLocalStorageTreeVisible());

  function getSystemTheme(): Theme {
    return globalThis.matchMedia?.('(prefers-color-scheme: dark)')?.matches ? 'dark' : 'light';
  }

  function availableThemes(): readonly Theme[] {
    return AVAILABLE_THEMES;
  }

  function listenToSystemThemeChanges(handler: (prefersDark: boolean) => void): (() => void) | undefined {
    const matchMedia = globalThis.matchMedia?.('(prefers-color-scheme: dark)');
    if (!matchMedia) {
      console.warn('matchMedia is not supported in this environment. System theme changes will not be detected.');
      return;
    }

    const listener = (e: MediaQueryListEvent) => handler(e.matches);
    matchMedia.addEventListener('change', listener);

    return () => {
      matchMedia.removeEventListener('change', listener);
    };
  }

  function setTheme(newTheme: Theme): void {
    theme.value = newTheme;
    setLocalStorageTheme(newTheme);
  }

  function setViewMode(mode: ViewMode): void {
    viewMode.value = mode;
    setLocalStorageViewMode(mode);
  }

  function setTreeVisible(visible: boolean): void {
    treeVisible.value = visible;
    setLocalStorageTreeVisible(visible);
  }

  return {
    theme,
    aboutModal,
    newDirectoryModal,
    renameDirectoryModal,
    deleteModal,
    uploadFileModal,
    createFileModal,
    renameFileModal,
    viewFileModal,
    moveModal,
    copyModal,
    bulkRenameModal,
    zipModal,
    unzipModal,
    cropImageModal,
    fileMetadataModal,
    trashModal,
    targetDirectoryPath,
    targetFilePath,
    targetArchiveFilePath,
    viewMode,
    treeVisible,
    setTheme,
    setViewMode,
    setTreeVisible,
    getSystemTheme,
    availableThemes,
    listenToSystemThemeChanges,
  };
});

export type UiStore = ReturnType<typeof useUiStore>;
