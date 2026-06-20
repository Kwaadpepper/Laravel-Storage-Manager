import { Path } from '@ts/types';
import { create } from 'zustand';

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
  const stored = localStorage[VIEW_MODE_KEY];
  return stored === 'tiles' ? 'tiles' : 'list';
}

function setLocalStorageViewMode(mode: ViewMode): void {
  localStorage[VIEW_MODE_KEY] = mode;
}

function getLocalStorageTreeVisible(): boolean {
  return localStorage[TREE_VISIBLE_KEY] !== 'false';
}

function setLocalStorageTreeVisible(visible: boolean): void {
  localStorage[TREE_VISIBLE_KEY] = String(visible);
}

function getLocalStorageTheme(): Theme {
  const storedTheme = localStorage[LOCAL_STORAGE_KEY];

  return storedTheme === 'light' || storedTheme === 'dark' || storedTheme === 'auto' ?
    storedTheme : 'auto';
}

function setLocalStorageTheme(theme: Theme): void {
  localStorage[LOCAL_STORAGE_KEY] = theme;
}

type UiState = {
  theme: Theme;
  setTheme: (theme: UiState['theme']) => void;
  aboutModal: ModalState
  setAboutModal: (state: ModalState) => void
  newDirectoryModal: ModalState
  setNewDirectoryModal: (state: ModalState) => void
  renameDirectoryModal: ModalState
  setRenameDirectoryModal: (state: ModalState) => void
  deleteModal: ModalState
  setDeleteModal: (state: ModalState) => void
  uploadFileModal: ModalState
  setUploadFileModal: (state: ModalState) => void
  createFileModal: ModalState
  setCreateFileModal: (state: ModalState) => void
  renameFileModal: ModalState
  setRenameFileModal: (state: ModalState) => void
  viewFileModal: ModalState
  setViewFileModal: (state: ModalState) => void
  moveModal: ModalState
  setMoveModal: (state: ModalState) => void
  copyModal: ModalState
  setCopyModal: (state: ModalState) => void
  targetDirectoryPath: Path | null
  setTargetDirectoryPath: (path: Path | null) => void
  targetFilePath: Path | null
  setTargetFilePath: (path: Path | null) => void
  viewMode: ViewMode
  setViewMode: (mode: ViewMode) => void
  treeVisible: boolean
  setTreeVisible: (visible: boolean) => void
}

export const useUiStore = create<UiState>((set) => ({
  theme: getLocalStorageTheme(),
  setTheme: (theme) => {
    set({ theme })
    setLocalStorageTheme(theme)
  },
  aboutModal: ModalState.Closed,
  setAboutModal: (state) => set({ aboutModal: state }),
  newDirectoryModal: ModalState.Closed,
  setNewDirectoryModal: (state) => set({ newDirectoryModal: state }),
  renameDirectoryModal: ModalState.Closed,
  setRenameDirectoryModal: (state) => set({ renameDirectoryModal: state }),
  deleteModal: ModalState.Closed,
  setDeleteModal: (state) => set({ deleteModal: state }),
  uploadFileModal: ModalState.Closed,
  setUploadFileModal: (state) => set({ uploadFileModal: state }),
  createFileModal: ModalState.Closed,
  setCreateFileModal: (state) => set({ createFileModal: state }),
  renameFileModal: ModalState.Closed,
  setRenameFileModal: (state) => set({ renameFileModal: state }),
  viewFileModal: ModalState.Closed,
  setViewFileModal: (state) => set({ viewFileModal: state }),
  moveModal: ModalState.Closed,
  setMoveModal: (state) => set({ moveModal: state }),
  copyModal: ModalState.Closed,
  setCopyModal: (state) => set({ copyModal: state }),
  targetDirectoryPath: null,
  setTargetDirectoryPath: (path) => set({ targetDirectoryPath: path }),
  targetFilePath: null,
  setTargetFilePath: (path) => set({ targetFilePath: path }),
  viewMode: getLocalStorageViewMode(),
  setViewMode: (mode) => {
    set({ viewMode: mode })
    setLocalStorageViewMode(mode)
  },
  treeVisible: getLocalStorageTreeVisible(),
  setTreeVisible: (visible) => {
    set({ treeVisible: visible })
    setLocalStorageTreeVisible(visible)
  },
}))
