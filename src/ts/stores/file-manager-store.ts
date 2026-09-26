import { Path, rootPath, TreeNodeDirectory, TreeNodeFile } from '@ts/types';
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
  const selectedNodes = ref<Record<Path, TreeNodeDirectory | TreeNodeFile>>({});
  const canNavigatePrevious = ref(false);
  const canNavigateNext = ref(false);
  const canNavigateUp = ref(false);

  function setCurrentPath(path: Path, searchParams?: URLSearchParams): void {
    currentPath.value = path;
    currentSearchParams.value = searchParams;
    selectedNodes.value = {};
  }

  function setFileNodes(nodes: TreeNodeFile[]): void {
    fileNodes.value = nodes;
  }

  function setDirectoryNodes(nodes: TreeNodeDirectory[]): void {
    directoryNodes.value = nodes;
  }

  function selectNodes(...nodes: (TreeNodeFile | TreeNodeDirectory)[]): void {
    selectedNodes.value = nodes
      .map((n) => ({ [n.path]: n }))
      .reduce((acc, curr) => ({ ...acc, ...curr }), {});
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
    selectedNodes,
    canNavigatePrevious,
    canNavigateNext,
    canNavigateUp,
    setCurrentPath,
    setFileNodes,
    setDirectoryNodes,
    selectNodes,
    setNavigationCapabilities,
  };
});

export type FileManagerStore = ReturnType<typeof useFileManagerStore>;
