import { TreeNodeState } from "@ts/stores";
import { Path, TreeNodeDirectory } from "@ts/types";
import { ChevronDown, ChevronRight, Folder, FolderOpen } from "lucide-react";

interface TreeNodeItemProps {
  directory: TreeNodeDirectory
  depth: number
  nodeState: TreeNodeState | undefined
  currentPath: Path
  nodes: Record<string, TreeNodeState>
  onToggle: (path: Path) => void
  onNavigate: (path: Path) => void
}

export default function TreeNodeItem({
  directory, depth, nodeState, currentPath, nodes, onToggle, onNavigate,
}: Readonly<TreeNodeItemProps>) {
  const isExpanded = nodeState?.expanded ?? false
  const isActive = currentPath === directory.path
  const hasChildren = directory.hasSubDirectories || (nodeState?.loaded === true && nodeState.children.length > 0)

  return (
    <li>
      <div
        data-path={directory.path}
        className={`flex items-center gap-1 rounded select-none${isActive ? ' bg-primary/20 text-primary' : ' hover:bg-base-200'}`}
        style={{ paddingLeft: `${depth * 14}px` }}
      >
        <button
          type="button"
          className={`btn btn-ghost btn-xs p-0 min-h-0 h-5 w-5 me-3 shrink-0${hasChildren ? '' : ' invisible pointer-events-none'}`}
          tabIndex={hasChildren ? 0 : -1}
          aria-label={isExpanded ? 'Collapse' : 'Expand'}
          onClick={(e) => { e.stopPropagation(); onToggle(directory.path) }}
        >
          {isExpanded ? <ChevronDown size={13} /> : <ChevronRight size={13} />}
        </button>
        <button
          type="button"
          className="flex items-center gap-1 flex-1 min-w-0 py-2 pr-2 cursor-pointer text-left overflow-hidden"
          title={directory.name}
          onClick={() => onNavigate(directory.path)}
        >
          {isExpanded
            ? <FolderOpen size={14} className="shrink-0 text-primary" />
            : <Folder size={14} className="shrink-0" />}
          <span className="truncate text-sm">{directory.name}</span>
        </button>
      </div>

      {isExpanded && nodeState?.loaded && nodeState.children.length > 0 && (
        <ul>
          {nodeState.children.map(child => (
            <TreeNodeItem
              key={child.path}
              directory={child}
              depth={depth + 1}
              nodeState={nodes[child.path]}
              currentPath={currentPath}
              nodes={nodes}
              onToggle={onToggle}
              onNavigate={onNavigate}
            />
          ))}
        </ul>
      )}
    </li>
  )
}
