import { Path, rootPath, TreeNodeDirectory, TreeNodeFile } from '@ts/types';
import { create } from 'zustand';

type FileManagerState = {
  currentPath: Path
  directoryNodes: TreeNodeDirectory[]
  fileNodes: TreeNodeFile[]
  selectedNodes: Record<Path, TreeNodeDirectory | TreeNodeFile>
  canNavigatePrevious: boolean
  canNavigateNext: boolean
  canNavigateUp: boolean
  setCurrentPath: (path: Path) => void
  setDirectoryNodes: (nodes: TreeNodeDirectory[]) => void
  setFileNodes: (nodes: TreeNodeFile[]) => void
  selectNodes: (...nodes: (TreeNodeFile | TreeNodeDirectory)[]) => void
}

export const useFileManagerStore = create<FileManagerState>((set) => ({
  currentPath: rootPath(),
  directoryNodes: [],
  fileNodes: [],
  selectedNodes: {},
  canNavigatePrevious: false,
  canNavigateNext: false,
  canNavigateUp: false,
  setCurrentPath: (path) => set({ currentPath: path, selectedNodes: {} }),
  setFileNodes: (nodes) => set({ fileNodes: nodes }),
  setDirectoryNodes: (nodes) => set({ directoryNodes: nodes }),
  selectNodes: (...nodes) => set({
    selectedNodes: nodes.map((n) => ({ [n.path]: n }))
      .reduce((acc, curr) => ({ ...acc, ...curr }), {})
  }),
}))
