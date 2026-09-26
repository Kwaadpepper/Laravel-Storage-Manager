import { Path, rootPath, TreeNodeDirectory } from '@ts/types';
import { defineStore } from 'pinia';
import { ref } from 'vue';

export type TreeNodeState = {
  children: TreeNodeDirectory[];
  loaded: boolean;
  expanded: boolean;
};

const getInitialState = (): Record<string, TreeNodeState> => ({
  [rootPath()]: { children: [], loaded: false, expanded: true },
});

export const useTreeStore = defineStore('tree', () => {
  const nodes = ref<Record<string, TreeNodeState>>(getInitialState());

  function setNodeChildren(path: Path, children: TreeNodeDirectory[]): void {
    nodes.value = {
      ...nodes.value,
      [path]: {
        ...(nodes.value[path] ?? { expanded: false }),
        children,
        loaded: true,
      },
    };
  }

  function toggleExpanded(path: Path): void {
    nodes.value = {
      ...nodes.value,
      [path]: {
        ...(nodes.value[path] ?? { children: [], loaded: false }),
        expanded: !(nodes.value[path]?.expanded ?? false),
      },
    };
  }

  function expandAncestors(path: Path): void {
    const parts = path.split('/').filter(Boolean);
    const ancestors: Path[] = [rootPath()];
    for (let i = 1; i < parts.length; i++) {
      ancestors.push(('/' + parts.slice(0, i).join('/')) as Path);
    }

    let changed = false;
    const newNodes = { ...nodes.value };
    for (const ancestor of ancestors) {
      if (!newNodes[ancestor]?.expanded) {
        newNodes[ancestor] = {
          ...(newNodes[ancestor] ?? { children: [], loaded: false }),
          expanded: true,
        };
        changed = true;
      }
    }
    if (changed) {
      nodes.value = newNodes;
    }
  }

  function reset(): void {
    nodes.value = getInitialState();
  }

  return {
    nodes,
    setNodeChildren,
    toggleExpanded,
    expandAncestors,
    reset,
  };
});

export type TreeStore = ReturnType<typeof useTreeStore>;
