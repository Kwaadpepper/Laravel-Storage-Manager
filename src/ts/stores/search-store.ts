import { TreeNodeDirectory, TreeNodeFile } from '@ts/types';
import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useSearchStore = defineStore('search', () => {
  const isSearching = ref(false);
  const hasFinished = ref(false);
  const files = ref<TreeNodeFile[]>([]);
  const directories = ref<TreeNodeDirectory[]>([]);
  const scannedPathsCount = ref(0);
  const queueLength = ref(0);
  const query = ref<string | undefined>(undefined);
  const extension = ref<string | undefined>(undefined);
  const minSize = ref<number | undefined>(undefined);
  const maxSize = ref<number | undefined>(undefined);

  function resetSearch(): void {
    isSearching.value = false;
    hasFinished.value = false;
    files.value = [];
    directories.value = [];
    scannedPathsCount.value = 0;
    queueLength.value = 0;
    query.value = undefined;
    extension.value = undefined;
    minSize.value = undefined;
    maxSize.value = undefined;
  }

  function setSearchParams(params: {
    query?: string;
    extension?: string;
    minSize?: number;
    maxSize?: number;
  }): void {
    if ('query' in params) query.value = params.query;
    if ('extension' in params) extension.value = params.extension;
    if ('minSize' in params) minSize.value = params.minSize;
    if ('maxSize' in params) maxSize.value = params.maxSize;
  }

  function setIsSearching(val: boolean): void {
    isSearching.value = val;
  }

  function setHasFinished(val: boolean): void {
    hasFinished.value = val;
  }

  function addResults(newFiles: TreeNodeFile[], newDirectories: TreeNodeDirectory[]): void {
    const filePaths = new Set(files.value.map(f => f.path));
    const uniqueFiles = newFiles.filter(f => !filePaths.has(f.path));

    const dirPaths = new Set(directories.value.map(d => d.path));
    const uniqueDirs = newDirectories.filter(d => !dirPaths.has(d.path));

    files.value = [...files.value, ...uniqueFiles];
    directories.value = [...directories.value, ...uniqueDirs];
  }

  function updateProgress(scanned: number, queue: number): void {
    scannedPathsCount.value = scanned;
    queueLength.value = queue;
  }

  return {
    isSearching,
    hasFinished,
    files,
    directories,
    scannedPathsCount,
    queueLength,
    query,
    extension,
    minSize,
    maxSize,
    resetSearch,
    setSearchParams,
    setIsSearching,
    setHasFinished,
    addResults,
    updateProgress,
  };
});

export type SearchStore = ReturnType<typeof useSearchStore>;
