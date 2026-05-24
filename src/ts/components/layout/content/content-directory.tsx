import { useContextualMenuRegistration } from "@ts/components/shared/use-contextual-menu-registration";
import { toAnchorName } from "@ts/stores";
import { rootPath, TreeNodeDirectory } from "@ts/types";
import { Folder, FolderOpen } from "lucide-react";
import { useMemo } from "react";

interface ContentDirectoryProps {
  readonly item: TreeNodeDirectory
}

export default function ContentDirectory({ item }: Readonly<ContentDirectoryProps>) {
  const anchorName = toAnchorName(item.path)

  const entries = useMemo(() => [
    { label: 'Open', onClick: () => console.log(`Open ${item.path}`) },
    { label: 'Rename', onClick: () => console.log(`Rename ${item.path}`) },
    { label: 'Delete', onClick: () => console.log(`Delete ${item.path}`) },
  ], [item.path])

  useContextualMenuRegistration(anchorName, entries)

  return (
    <>
      <div data-contextual-menu={anchorName} tabIndex={-1} style={{ anchorName }} className="table-cell p-2">
        <span className="flex items-center gap-2">
          {item.path === rootPath() ? <Folder size={16} className="text-warning" /> : <FolderOpen size={16} className="text-warning" />}
          {item.path === rootPath() ? 'root' : item.name}
        </span>
      </div>
      <div className="table-cell p-2"><span className="badge badge-ghost badge-sm">Directory</span></div>
      <div className="table-cell p-2">-</div>
      <div className="table-cell p-2">-</div>
    </>
  )
}
