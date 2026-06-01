import { useContextualMenuRegistration } from "@ts/components/shared/use-contextual-menu-registration";
import { useContainer } from "@ts/container";
import { ModalState, toAnchorName, useUiStore } from "@ts/stores";
import { rootPath, TreeNodeDirectory } from "@ts/types";
import { Folder, FolderOpen } from "lucide-react";
import { useMemo } from "react";

interface ContentDirectoryProps {
  readonly asTile?: boolean
  readonly item: TreeNodeDirectory
}

export default function ContentDirectory({ item, asTile = false }: Readonly<ContentDirectoryProps>) {
  const { setRenameDirectoryModal, setDeleteDirectoryModal, setTargetDirectoryPath } = useUiStore()
  const container = useContainer()
  const navigationService = container.resolve('navigationService')

  const anchorName = toAnchorName(item.path)
  const isRoot = item.path === rootPath()

  const entries = useMemo(() => [
    { label: 'Open', onClick: () => navigationService.navigateTo(item.path) },
    { label: 'Rename', onClick: () => { setTargetDirectoryPath(item.path); setRenameDirectoryModal(ModalState.Opened) } },
    { label: 'Delete', onClick: () => { setTargetDirectoryPath(item.path); setDeleteDirectoryModal(ModalState.Opened) } },
  ], [item.path])

  useContextualMenuRegistration(anchorName, entries)

  if (asTile) {
    return (
      <div
        data-contextual-menu={anchorName}
        style={{ anchorName }}
        className="flex flex-col items-center gap-1 p-3 rounded-lg w-24 select-none"
      >
        {isRoot
          ? <Folder size={36} className="text-warning" />
          : <FolderOpen size={36} className="text-warning" />
        }
        <span className="text-xs text-center truncate w-full">{isRoot ? 'root' : item.name}</span>
      </div>
    )
  }

  return (
    <>
      <div data-contextual-menu={anchorName} tabIndex={-1} style={{ anchorName }} className="table-cell select-none p-2">
        <span className="flex items-center gap-2">
          {item.path === rootPath() ? <Folder size={16} className="text-warning" /> : <FolderOpen size={16} className="text-warning" />}
          {item.path === rootPath() ? 'root' : item.name}
        </span>
      </div>
      <div className="table-cell select-none p-2"><span className="badge badge-ghost badge-sm">Directory</span></div>
      <div className="table-cell select-none p-2">-</div>
      <div className="table-cell select-none p-2">-</div>
    </>
  )
}
