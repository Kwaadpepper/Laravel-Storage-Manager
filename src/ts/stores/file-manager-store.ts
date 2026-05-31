import { Path, rootPath, TreeNodeDirectory, TreeNodeFile } from '@ts/types';
import { create } from 'zustand';

type FileManagerState = {
  currentPath: Path
  directories: TreeNodeDirectory[]
  files: TreeNodeFile[]
  selectedFile: TreeNodeFile | TreeNodeDirectory | null
  canNavigatePrevious: boolean
  canNavigateNext: boolean
  canNavigateUp: boolean
  setCurrentPath: (path: Path) => void
  setFiles: (files: TreeNodeFile[]) => void
  selectNode: (file: TreeNodeFile | TreeNodeDirectory | null) => void
}

export const useFileManagerStore = create<FileManagerState>((set) => ({
  currentPath: rootPath(),
  directories: [],
  files: [],
  selectedFile: null,
  canNavigatePrevious: false,
  canNavigateNext: false,
  canNavigateUp: false,
  setCurrentPath: (path) => set({ currentPath: path, selectedFile: null }),
  setFiles: (files) => set({ files }),
  selectNode: (file) => set({ selectedFile: file }),
}))
