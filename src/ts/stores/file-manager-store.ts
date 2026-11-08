import { PaginationMeta, Path, rootPath, TreeNodeDirectory, TreeNodeFile } from '@ts/types';
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

function computeBaseName(path: Path): string {
  if (path === rootPath()) {
    return 'root';
  }
  const parts = path.split('/').filter(Boolean);
  return parts.at(-1) ?? 'root';
}

export const useFileManagerStore = defineStore('fileManager', () => {
  const currentPath = ref<Path>(rootPath());
  const currentSearchParams = ref<URLSearchParams | undefined>(undefined);
  const currentBaseName = computed(() => computeBaseName(currentPath.value));
  const directoryNodes = ref<TreeNodeDirectory[]>([]);
  const fileNodes = ref<TreeNodeFile[]>([]);
  const pagination = ref<PaginationMeta | null>(null);
  const isLoadingMore = ref(false);
  const selectedNodes = ref<Record<Path, TreeNodeDirectory | TreeNodeFile>>({});
  const canNavigatePrevious = ref(false);
  const canNavigateNext = ref(false);
  const canNavigateUp = ref(false);

  function setCurrentPath(path: Path, searchParams?: URLSearchParams): void {
    currentPath.value = path;
    currentSearchParams.value = searchParams;
    selectedNodes.value = {};
    pagination.value = null;
  }

  function setFileNodes(nodes: TreeNodeFile[], meta?: PaginationMeta | null): void {
    fileNodes.value = nodes;
    pagination.value = meta ?? null;
  }

  function appendFileNodes(nodes: TreeNodeFile[], meta?: PaginationMeta | null): void {
    fileNodes.value = [...fileNodes.value, ...nodes];
    pagination.value = meta ?? null;
  }

  function setDirectoryNodes(nodes: TreeNodeDirectory[]): void {
    directoryNodes.value = nodes;
  }

  function setIsLoadingMore(loading: boolean): void {
    isLoadingMore.value = loading;
  }

  function setPagination(meta: PaginationMeta | null): void {
    pagination.value = meta;
  }

  function selectNodes(...nodes: (TreeNodeFile | TreeNodeDirectory)[]): void {
    const selected: Record<Path, TreeNodeDirectory | TreeNodeFile> = {};
    for (const node of nodes) {
      selected[node.path] = node;
    }
    selectedNodes.value = selected;
  }

  function setNavigationCapabilities(caps: {
    canNavigatePrevious: boolean;
    canNavigateNext: boolean;
    canNavigateUp: boolean;
  }): void {
    canNavigatePrevious.value = caps.canNavigatePrevious;
    canNavigateNext.value = caps.canNavigateNext;
    canNavigateUp.value = caps.canNavigateUp;
  }

  return {
    currentPath,
    currentSearchParams,
    currentBaseName,
    directoryNodes,
    fileNodes,
    pagination,
    isLoadingMore,
    selectedNodes,
    canNavigatePrevious,
    canNavigateNext,
    canNavigateUp,
    setCurrentPath,
    setFileNodes,
    appendFileNodes,
    setDirectoryNodes,
    setIsLoadingMore,
    setPagination,
    selectNodes,
    setNavigationCapabilities,
  };
});

export type FileManagerStore = ReturnType<typeof useFileManagerStore>;
