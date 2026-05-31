import { useContextualMenuRegistration } from "@ts/components/shared/use-contextual-menu-registration";
import { useContainer } from "@ts/container";
import { ModalState, toAnchorName, useFileManagerStore, useUiStore } from "@ts/stores";
import { rootPath, TreeNodeDirectory } from "@ts/types";
import { Folder, FolderOpen } from "lucide-react";
import { useMemo } from "react";

interface ContentTileDirectoryProps {
  readonly item: TreeNodeDirectory
}

export default function ContentTileDirectory({ item }: Readonly<ContentTileDirectoryProps>) {
  const { setRenameDirectoryModal, setDeleteDirectoryModal, setTargetDirectoryPath } = useUiStore()
  const { selectedFile } = useFileManagerStore()
  const container = useContainer()
  const navigationService = container.resolve('navigationService')

  const isSelected = selectedFile?.path === item.path

  const anchorName = toAnchorName(item.path)

  const entries = useMemo(() => [
    { label: 'Open', onClick: () => navigationService.navigateTo(item.path) },
    { label: 'Rename', onClick: () => { setTargetDirectoryPath(item.path); setRenameDirectoryModal(ModalState.Opened) } },
    { label: 'Delete', onClick: () => { setTargetDirectoryPath(item.path); setDeleteDirectoryModal(ModalState.Opened) } },
  ], [item.path])

  useContextualMenuRegistration(anchorName, entries)

  const isRoot = item.path === rootPath()

  return (
    <button
      type="button"
      data-contextual-menu={anchorName}
      style={{ anchorName }}
      className={`flex flex-col items-center gap-1 p-3 rounded-lg hover:bg-accent/30 hover:cursor-pointer w-24${isSelected ? ' bg-primary/20 ring-1 ring-primary' : ''}`}
      title={isRoot ? 'root' : item.name}
      onClick={() => navigationService.navigateTo(item.path)}
      onKeyDown={(e) => { if (e.key === 'Enter') navigationService.navigateTo(item.path) }}
    >
      {isRoot
        ? <Folder size={36} className="text-warning" />
        : <FolderOpen size={36} className="text-warning" />
      }
      <span className="text-xs text-center truncate w-full">{isRoot ? 'root' : item.name}</span>
    </button>
  )
}
