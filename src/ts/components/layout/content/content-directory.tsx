import { useContextualMenuRegistration } from "@ts/components/shared/use-contextual-menu-registration";
import { useContainer } from "@ts/container";
import { useClipboardStore, ModalState, toAnchorName, useFileManagerStore, useUiStore } from "@ts/stores";
import { rootPath, TreeNodeDirectory } from "@ts/types";
import { Folder, FolderOpen } from "lucide-react";
import { useMemo } from "react";

interface ContentDirectoryProps {
  readonly asTile?: boolean
  readonly item: TreeNodeDirectory
}

export default function ContentDirectory({ item, asTile = false }: Readonly<ContentDirectoryProps>) {
  const { setRenameDirectoryModal, setDeleteDirectoryModal, setTargetDirectoryPath } = useUiStore()
  const { selectedNodes } = useFileManagerStore()
  const { hasEntries } = useClipboardStore()
  const container = useContainer()
  const navigationService = container.resolve('navigationService')
  const clipboardService = container.resolve('clipboardService')
  const fileManagerService = container.resolve('fileManagerService')
  const toastService = container.resolve('toastService')

  const anchorName = toAnchorName(item.path)
  const isRoot = item.path === rootPath()

  const entries = useMemo(() => [
    { label: 'Open', onClick: () => navigationService.navigateTo(item.path) },
    { separator: true as const },
    { label: 'Cut', onClick: () => {
      const paths = Object.values(selectedNodes)
      const nodes = paths.length > 0 ? paths : [item]
      clipboardService.setConsumingMode(true)
      clipboardService.addEntry(...nodes.map(n => n.path))
      toastService.pushToast({ message: `${nodes.length} item(s) cut to clipboard.`, type: 'info' })
    }},
    { label: 'Copy', onClick: () => {
      const paths = Object.values(selectedNodes)
      const nodes = paths.length > 0 ? paths : [item]
      clipboardService.setConsumingMode(false)
      clipboardService.addEntry(...nodes.map(n => n.path))
      toastService.pushToast({ message: `${nodes.length} item(s) copied to clipboard.`, type: 'info' })
    }},
    ...(hasEntries ? [{ label: 'Paste', onClick: async () => {
      const isCut = clipboardService.getIsConsumingMode()
      const entry = clipboardService.getLastEntry()
      if (!entry || entry.length === 0) {
        toastService.pushToast({ message: 'Clipboard is empty.', type: 'info' })
        return
      }
      try {
        for (const path of entry) {
          if (isCut) {
            await fileManagerService.move(path, item.path)
          } else {
            await fileManagerService.copy(path, item.path)
          }
        }
        clipboardService.clearEntries()
        toastService.pushToast({ message: 'Pasted successfully.', type: 'success' })
        await navigationService.refreshCurrentPath()
      } catch (e: any) {
        toastService.pushToast({ message: e.message || 'Failed to paste.', type: 'error' })
      }
    }}] : []),
    { separator: true as const },
    { label: 'Rename', onClick: () => { setTargetDirectoryPath(item.path); setRenameDirectoryModal(ModalState.Opened) } },
    { label: 'Delete', onClick: () => { setTargetDirectoryPath(item.path); setDeleteDirectoryModal(ModalState.Opened) } },
  ], [item.path, selectedNodes, hasEntries])

  useContextualMenuRegistration(anchorName, entries)

  if (asTile) {
    return (
      <div
        data-selectable
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
      <div data-selectable data-contextual-menu={anchorName} tabIndex={-1} style={{ anchorName }} className="table-cell select-none p-2">
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
