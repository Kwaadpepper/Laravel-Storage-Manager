import { Path, rootPath, TreeNodeDirectory, TreeNodeFile } from '@ts/types';
import { create } from 'zustand';

type State = {
  currentPath: Path
  currentBaseName: string
  directoryNodes: readonly TreeNodeDirectory[]
  fileNodes: readonly TreeNodeFile[]
  selectedNodes: Readonly<Record<Path, TreeNodeDirectory | TreeNodeFile>>
  canNavigatePrevious: boolean
  canNavigateNext: boolean
  canNavigateUp: boolean
}
type Actions = {
  setCurrentPath: (path: Path) => void
  setDirectoryNodes: (nodes: TreeNodeDirectory[]) => void
  setFileNodes: (nodes: TreeNodeFile[]) => void
  selectNodes: (...nodes: (TreeNodeFile | TreeNodeDirectory)[]) => void
}

type FileManagerState = Readonly<State> & Actions;

function computeBaseName(path: Path): string {
  if (path === rootPath()) {
    return 'root'
  }
  const parts = path.split('/').filter(Boolean)
  return parts.at(-1) ?? 'root'
}

export const useFileManagerStore = create<FileManagerState>((set) => ({
  currentPath: rootPath(),
  currentBaseName: computeBaseName(rootPath()),
  directoryNodes: [],
  fileNodes: [],
  selectedNodes: {},
  canNavigatePrevious: false,
  canNavigateNext: false,
  canNavigateUp: false,

  setCurrentPath: (path) => set({
    currentPath: path,
    currentBaseName: computeBaseName(path),
    selectedNodes: {}
  }),
  setFileNodes: (nodes) => set({ fileNodes: nodes }),
  setDirectoryNodes: (nodes) => set({ directoryNodes: nodes }),
  selectNodes: (...nodes) => set({
    selectedNodes: nodes.map((n) => ({ [n.path]: n }))
      .reduce((acc, curr) => ({ ...acc, ...curr }), {})
  }),
}));
