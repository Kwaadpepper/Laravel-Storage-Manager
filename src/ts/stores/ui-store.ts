import { Path } from '@ts/types';
import { defineStore } from 'pinia';
import { ref } from 'vue';

export type Theme = 'auto' | 'light' | 'dark';

export enum ModalState {
  Closed = 'closed',
  Opened = 'opened',
}

const LOCAL_STORAGE_KEY = 'sm-theme';
const VIEW_MODE_KEY = 'sm-view-mode';
const TREE_VISIBLE_KEY = 'sm-tree-visible';

export type ViewMode = 'list' | 'tiles';

function getLocalStorageViewMode(): ViewMode {
  const stored = localStorage.getItem(VIEW_MODE_KEY);
  return stored === 'tiles' ? 'tiles' : 'list';
}

function setLocalStorageViewMode(mode: ViewMode): void {
  localStorage.setItem(VIEW_MODE_KEY, mode);
}

function getLocalStorageTreeVisible(): boolean {
  return localStorage.getItem(TREE_VISIBLE_KEY) !== 'false';
}

function setLocalStorageTreeVisible(visible: boolean): void {
  localStorage.setItem(TREE_VISIBLE_KEY, String(visible));
}

function getLocalStorageTheme(): Theme {
  const storedTheme = localStorage.getItem(LOCAL_STORAGE_KEY);
  return storedTheme === 'light' || storedTheme === 'dark' || storedTheme === 'auto'
    ? storedTheme
    : 'auto';
}

function setLocalStorageTheme(theme: Theme): void {
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
  const targetDirectoryPath = ref<Path | null>(null);
  const targetFilePath = ref<Path | null>(null);
  const viewMode = ref<ViewMode>(getLocalStorageViewMode());
  const treeVisible = ref<boolean>(getLocalStorageTreeVisible());

  function setTheme(newTheme: Theme): void {
    theme.value = newTheme;
    setLocalStorageTheme(newTheme);
  }

  function setAboutModal(state: ModalState): void {
    aboutModal.value = state;
  }

  function setNewDirectoryModal(state: ModalState): void {
    newDirectoryModal.value = state;
  }

  function setRenameDirectoryModal(state: ModalState): void {
    renameDirectoryModal.value = state;
  }

  function setDeleteModal(state: ModalState): void {
    deleteModal.value = state;
  }

  function setUploadFileModal(state: ModalState): void {
    uploadFileModal.value = state;
  }

  function setCreateFileModal(state: ModalState): void {
    createFileModal.value = state;
  }

  function setRenameFileModal(state: ModalState): void {
    renameFileModal.value = state;
  }

  function setViewFileModal(state: ModalState): void {
    viewFileModal.value = state;
  }

  function setMoveModal(state: ModalState): void {
    moveModal.value = state;
  }

  function setCopyModal(state: ModalState): void {
    copyModal.value = state;
  }

  function setTargetDirectoryPath(path: Path | null): void {
    targetDirectoryPath.value = path;
  }

  function setTargetFilePath(path: Path | null): void {
    targetFilePath.value = path;
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
    targetDirectoryPath,
    targetFilePath,
    viewMode,
    treeVisible,
    setTheme,
    setAboutModal,
    setNewDirectoryModal,
    setRenameDirectoryModal,
    setDeleteModal,
    setUploadFileModal,
    setCreateFileModal,
    setRenameFileModal,
    setViewFileModal,
    setMoveModal,
    setCopyModal,
    setTargetDirectoryPath,
    setTargetFilePath,
    setViewMode,
    setTreeVisible,
  };
});

export type UiStore = ReturnType<typeof useUiStore>;
