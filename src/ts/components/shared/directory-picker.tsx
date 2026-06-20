import DiskSelector from "@ts/components/shared/disk-selector";
import { useContainer } from "@ts/container";
import { useTreeStore } from "@ts/stores";
import { Path, rootPath } from "@ts/types";
import { ChevronDown, ChevronRight, Folder, FolderOpen } from "lucide-react";
import TreeNodeItem from "../layout/tree-view/tree-node-item";

interface DirectoryPickerProps {
  selectedPath: Path | null
  onSelect: (path: Path) => void
}

export default function DirectoryPicker({ selectedPath, onSelect }: Readonly<DirectoryPickerProps>) {
  const container = useContainer()
  const navigationService = container.resolve('navigationService')
  const { nodes } = useTreeStore()

  const rootState = nodes[rootPath()]
  const rootActive = selectedPath === rootPath()

  return (
    <div className="bg-base-200/50 rounded-box border border-base-200 shadow-inner w-full overflow-y-auto h-[400px]">
      <div className="px-3 pt-2 pb-1 border-b border-base-200 sticky top-0 bg-base-100 z-10 shadow-sm">
        <DiskSelector className="w-full" />
      </div>
      <ul className="p-2 text-sm">
        <li>
          <div data-path={rootPath()} className={`flex items-center gap-1 rounded select-none${rootActive ? ' bg-primary text-primary-content' : ' hover:bg-base-300'}`}>
            <button
              type="button"
              className="btn btn-ghost btn-xs p-0 min-h-0 h-5 w-5 shrink-0"
              aria-label={rootState?.expanded ? 'Collapse root' : 'Expand root'}
              onClick={(e) => { e.stopPropagation(); navigationService.loadTreeNode(rootPath()) }}
            >
              {rootState?.expanded ? <ChevronDown size={13} /> : <ChevronRight size={13} />}
            </button>
            <button
              type="button"
              className="flex items-center gap-1 flex-1 min-w-0 py-1 pr-2 text-left"
              onClick={() => onSelect(rootPath())}
            >
              {rootState?.expanded
                ? <FolderOpen size={14} className="shrink-0" />
                : <Folder size={14} className="shrink-0" />}
              <span className="text-sm font-medium">/</span>
            </button>
          </div>

          {rootState?.expanded && rootState.loaded && (
            <ul>
              {rootState.children.length === 0 ? (
                <li className="text-base-content/40 text-xs px-4 py-1">Empty</li>
              ) : rootState.children.map(dir => (
                <TreeNodeItem
                  key={dir.path}
                  directory={dir}
                  depth={1}
                  nodeState={nodes[dir.path]}
                  currentPath={selectedPath ?? ('' as Path)}
                  nodes={nodes}
                  onToggle={(path) => navigationService.loadTreeNode(path)}
                  onNavigate={(path) => onSelect(path)}
                />
              ))}
            </ul>
          )}
        </li>
      </ul>
    </div>
  )
}
