import { TreeNodeDirectory, TreeNodeFile } from '@ts/types';
import { create } from 'zustand';

type State = {
  isSearching: boolean
  hasFinished: boolean
  files: TreeNodeFile[]
  directories: TreeNodeDirectory[]
  scannedPathsCount: number
  queueLength: number
  query: string | undefined
  extension: string | undefined
  minSize: number | undefined
  maxSize: number | undefined
}

type Actions = {
  resetSearch: () => void
  setSearchParams: (params: { query?: string, extension?: string, minSize?: number, maxSize?: number }) => void
  setIsSearching: (isSearching: boolean) => void
  setHasFinished: (hasFinished: boolean) => void
  addResults: (files: TreeNodeFile[], directories: TreeNodeDirectory[]) => void
  updateProgress: (scannedPathsCount: number, queueLength: number) => void
}

export const useSearchStore = create<State & Actions>((set) => ({
  isSearching: false,
  hasFinished: false,
  files: [],
  directories: [],
  scannedPathsCount: 0,
  queueLength: 0,
  query: undefined,
  extension: undefined,
  minSize: undefined,
  maxSize: undefined,

  resetSearch: () => set({
    isSearching: false,
    hasFinished: false,
    files: [],
    directories: [],
    scannedPathsCount: 0,
    queueLength: 0,
    query: undefined,
    extension: undefined,
    minSize: undefined,
    maxSize: undefined,
  }),

  setSearchParams: (params) => set((state) => ({ ...state, ...params })),
  setIsSearching: (isSearching) => set({ isSearching }),
  setHasFinished: (hasFinished) => set({ hasFinished }),
  addResults: (newFiles, newDirectories) => set((state) => {
    const filePaths = new Set(state.files.map(f => f.path))
    const uniqueFiles = newFiles.filter(f => !filePaths.has(f.path))

    const dirPaths = new Set(state.directories.map(d => d.path))
    const uniqueDirs = newDirectories.filter(d => !dirPaths.has(d.path))

    return {
      files: [...state.files, ...uniqueFiles],
      directories: [...state.directories, ...uniqueDirs],
    }
  }),
  updateProgress: (scannedPathsCount, queueLength) => set({ scannedPathsCount, queueLength }),
}));
