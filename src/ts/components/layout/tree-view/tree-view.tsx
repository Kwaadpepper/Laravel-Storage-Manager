import DiskSelector from "@ts/components/shared/disk-selector";
import { useContainer } from "@ts/container";
import { useFileManagerStore, useTreeStore } from "@ts/stores";
import { rootPath } from "@ts/types";
import { ChevronDown, ChevronRight, Folder, FolderOpen } from "lucide-react";
import TreeNodeItem from "./tree-node-item";

interface TreeViewProps { }

export default function TreeView(_: Readonly<TreeViewProps>) {
  const container = useContainer()
  const navigationService = container.resolve('navigationService')
  const { currentPath } = useFileManagerStore()
  const { nodes } = useTreeStore()

  const rootState = nodes[rootPath()]
  const rootActive = currentPath === rootPath()

  return (
    <div className="bg-base-100 rounded-box shadow-sm w-full overflow-y-auto h-full">
      <div className="px-3 pt-2 pb-1 border-b border-base-200">
        <DiskSelector className="w-full" />
      </div>
      <ul className="p-2 text-sm">
        <li>
          <div className={`flex items-center gap-1 rounded select-none${rootActive ? ' bg-primary/20 text-primary' : ' hover:bg-base-200'}`}>
            <button
              type="button"
              className="btn btn-ghost btn-xs p-0 min-h-0 h-5 w-5 shrink-0"
              aria-label={rootState?.expanded ? 'Collapse root' : 'Expand root'}
              onClick={() => navigationService.loadTreeNode(rootPath())}
            >
              {rootState?.expanded ? <ChevronDown size={13} /> : <ChevronRight size={13} />}
            </button>
            <button
              type="button"
              className="flex items-center gap-1 flex-1 min-w-0 py-1 pr-2 text-left"
              onClick={() => navigationService.navigateTo(rootPath())}
            >
              {rootState?.expanded
                ? <FolderOpen size={14} className="shrink-0 text-primary" />
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
                  currentPath={currentPath}
                  nodes={nodes}
                  onToggle={(path) => navigationService.loadTreeNode(path)}
                  onNavigate={(path) => navigationService.navigateTo(path)}
                />
              ))}
            </ul>
          )}
        </li>
      </ul>
    </div>
  )
}
