import { Path, rootPath, TreeNodeDirectory, TreeNodeFile } from '@ts/types';
import { create } from 'zustand';

type FileManagerState = {
  currentPath: Path
  directoryNodes: TreeNodeDirectory[]
  fileNodes: TreeNodeFile[]
  selectedNode: TreeNodeFile | TreeNodeDirectory | null
  canNavigatePrevious: boolean
  canNavigateNext: boolean
  canNavigateUp: boolean
  setCurrentPath: (path: Path) => void
  setDirectoryNodes: (nodes: TreeNodeDirectory[]) => void
  setFileNodes: (nodes: TreeNodeFile[]) => void
  selectNode: (node: TreeNodeFile | TreeNodeDirectory | null) => void
}

export const useFileManagerStore = create<FileManagerState>((set) => ({
  currentPath: rootPath(),
  directoryNodes: [],
  fileNodes: [],
  selectedNode: null,
  canNavigatePrevious: false,
  canNavigateNext: false,
  canNavigateUp: false,
  setCurrentPath: (path) => set({ currentPath: path, selectedNode: null }),
  setFileNodes: (nodes) => set({ fileNodes: nodes }),
  setDirectoryNodes: (nodes) => set({ directoryNodes: nodes }),
  selectNode: (node) => set({ selectedNode: node }),
}))
