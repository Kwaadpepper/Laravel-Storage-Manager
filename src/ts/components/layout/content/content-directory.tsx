import { useContextualMenuRegistration } from "@ts/components/shared/use-contextual-menu-registration";
import { useContainer } from "@ts/container";
import { useClipboardStore, ModalState, toAnchorName, useFileManagerStore, useUiStore, useDiskStore } from "@ts/stores";
import { rootPath, TreeNodeDirectory } from "@ts/types";
import { Folder, FolderOpen } from "lucide-react";
import { useMemo } from "react";

interface ContentDirectoryProps {
  readonly asTile?: boolean
  readonly item: TreeNodeDirectory
}

export default function ContentDirectory({ item, asTile = false }: Readonly<ContentDirectoryProps>) {
  const { setRenameDirectoryModal, setDeleteModal, setTargetDirectoryPath } = useUiStore()
  const { selectedNodes, selectNodes } = useFileManagerStore()
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
      
      const eventQueueService = container.resolve('eventQueueService')
      const events = entry.map(path => ({
        id: `${isCut ? 'move' : 'copy'}-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`,
        type: (isCut ? 'MOVE' : 'COPY') as 'MOVE' | 'COPY',
        sourcePath: path,
        destinationPath: item.path,
        execute: async () => {
          if (isCut) {
            await fileManagerService.move(path, item.path)
          } else {
            await fileManagerService.copy(path, item.path)
          }
        }
      }))

      eventQueueService.pushBatch(events)
      clipboardService.clearEntries()
    }}] : []),
    { separator: true as const },
    { label: 'Copy Path', onClick: () => {
      navigator.clipboard.writeText(item.path).catch(() => {})
      toastService.pushToast({ message: 'Path copied to clipboard.', type: 'success' })
    }},
    { label: 'Copy Link', onClick: () => {
      const url = new URL(globalThis.location.href)
      const disk = useDiskStore.getState().currentDisk
      const hashPath = `/${disk}${item.path}`
      url.hash = hashPath.split('/').map(encodeURIComponent).join('/')
      navigator.clipboard.writeText(url.toString()).catch(() => {})
      toastService.pushToast({ message: 'Link copied to clipboard.', type: 'success' })
    }},
    { separator: true as const },
    { label: 'Rename', onClick: () => { 
        if (!selectedNodes[item.path]) selectNodes(item);
        setTargetDirectoryPath(item.path); 
        setRenameDirectoryModal(ModalState.Opened);
    } },
    { label: 'Delete', onClick: () => { 
        if (!selectedNodes[item.path]) selectNodes(item);
        setDeleteModal(ModalState.Opened);
    } },
  ], [item.path, item, selectedNodes, selectNodes, hasEntries])

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
        {item.visibility && (
          <span className={`badge badge-xs mt-1 ${item.visibility === 'public' ? 'badge-success badge-outline' : 'badge-neutral badge-outline'}`}>
            {item.visibility}
          </span>
        )}
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
      <div className="table-cell select-none p-2">
        {item.visibility && (
          <span className={`badge badge-sm ${item.visibility === 'public' ? 'badge-success badge-outline' : 'badge-neutral badge-outline'}`}>
            {item.visibility}
          </span>
        )}
      </div>
    </>
  )
}
