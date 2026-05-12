import { Path, rootPath, TreeNode, TreeNodeDirectory, TreeNodeFile } from '@ts/types';
import { create } from 'zustand';

type FileManagerState = {
  currentPath: Path
  directories: TreeNodeDirectory[]
  files: TreeNodeFile[]
  selectedFile: TreeNode | null
  setCurrentPath: (path: Path) => void
  setFiles: (files: TreeNodeFile[]) => void
  selectNode: (file: TreeNode | null) => void
}

export const useFileManagerStore = create<FileManagerState>((set) => ({
  currentPath: rootPath(),
  directories: [],
  files: [],
  selectedFile: null,
  setCurrentPath: (path) => set({ currentPath: path, selectedFile: null }),
  setFiles: (files) => set({ files }),
  selectNode: (file) => set({ selectedFile: file }),
}))
