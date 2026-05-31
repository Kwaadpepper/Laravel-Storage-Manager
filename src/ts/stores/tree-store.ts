import { Path, rootPath, TreeNodeDirectory } from '@ts/types';
import { create } from 'zustand';

export type TreeNodeState = {
    children: TreeNodeDirectory[]
    loaded: boolean
    expanded: boolean
}

type TreeState = {
    nodes: Record<string, TreeNodeState>
    setNodeChildren: (path: Path, children: TreeNodeDirectory[]) => void
    toggleExpanded: (path: Path) => void
    expandAncestors: (path: Path) => void
    reset: () => void
}

const initialState: TreeState['nodes'] = {
    [rootPath()]: { children: [], loaded: false, expanded: true },
}

export const useTreeStore = create<TreeState>((set) => ({
    nodes: initialState,

    setNodeChildren: (path, children) =>
        set(state => ({
            nodes: {
                ...state.nodes,
                [path]: {
                    ...(state.nodes[path] ?? { expanded: false }),
                    children,
                    loaded: true,
                },
            },
        })),

    toggleExpanded: (path) =>
        set(state => ({
            nodes: {
                ...state.nodes,
                [path]: {
                    ...(state.nodes[path] ?? { children: [], loaded: false }),
                    expanded: !(state.nodes[path]?.expanded ?? false),
                },
            },
        })),

    expandAncestors: (path) =>
        set(state => {
            const parts = path.split('/').filter(Boolean)
            const ancestors: Path[] = [rootPath()]
            for (let i = 1; i < parts.length; i++) {
                ancestors.push(('/' + parts.slice(0, i).join('/')) as Path)
            }

            let nodes = state.nodes
            let changed = false
            for (const ancestor of ancestors) {
                if (!nodes[ancestor]?.expanded) {
                    nodes = {
                        ...nodes,
                        [ancestor]: {
                            ...(nodes[ancestor] ?? { children: [], loaded: false }),
                            expanded: true,
                        },
                    }
                    changed = true
                }
            }
            return changed ? { nodes } : state
        }),

    reset: () => set({ nodes: initialState }),
}))
