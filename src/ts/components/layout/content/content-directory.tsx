import { useContextualMenuRegistration } from "@ts/components/shared/use-contextual-menu-registration";
import { useContainer } from "@ts/container";
import { toAnchorName } from "@ts/stores";
import { rootPath, TreeNodeDirectory } from "@ts/types";
import { Folder, FolderOpen } from "lucide-react";
import { useMemo } from "react";

interface ContentDirectoryProps {
  readonly item: TreeNodeDirectory
}

export default function ContentDirectory({ item }: Readonly<ContentDirectoryProps>) {
  const container = useContainer()
  const navigationService = container.resolve('navigationService')
  const fileManagerService = container.resolve('fileManagerService')

  const anchorName = toAnchorName(item.path)

  const entries = useMemo(() => [
    { label: 'Open', onClick: () => navigationService.navigateTo(item.path) },
    { label: 'Rename', onClick: () => fileManagerService.rename(item.path) },
    { label: 'Delete', onClick: () => fileManagerService.deleteDirectory(item.path) },
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
