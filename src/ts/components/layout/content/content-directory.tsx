import { rootPath, TreeNodeDirectory } from "@ts/types";
import { Folder, FolderOpen } from "lucide-react";

type ContentDirectoryProps = {
  readonly item: TreeNodeDirectory
}

export default function ContentDirectory({ item }: ContentDirectoryProps) {

  return (
    <>
      <div className="table-cell p-2">
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
