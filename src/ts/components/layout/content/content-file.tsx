import FileSize from "@ts/components/shared/file-size";
import { useContextualMenuRegistration } from "@ts/components/shared/use-contextual-menu-registration";
import { useContainer } from "@ts/container";
import { ModalState, toAnchorName, useFileManagerStore, useUiStore } from "@ts/stores";
import { TreeNodeFile } from "@ts/types";
import { FileIcon } from "lucide-react";
import { useMemo } from "react";

interface ContentFileProps {
  readonly asTile?: boolean
  readonly item: TreeNodeFile
}

export default function ContentFile({ item, asTile = false }: Readonly<ContentFileProps>) {
  const { setRenameFileModal, setDeleteFileModal, setViewFileModal, setTargetFilePath } = useUiStore()
  const { selectedNodes } = useFileManagerStore()
  const container = useContainer()
  const downloadService = container.resolve('downloadService')
  const clipboardService = container.resolve('clipboardService')
  const toastService = container.resolve('toastService')
  const anchorName = toAnchorName(item.path)

  async function download() {
    try {
      const blob = await downloadService.downloadFile(item.path)
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = item.name
      a.click()
      URL.revokeObjectURL(url)
    } catch {
      toastService.pushToast({ message: 'Failed to download file.', type: 'error' })
    }
  }

  const entries = useMemo(() => [
    { label: 'View', onClick: () => { setTargetFilePath(item.path); setViewFileModal(ModalState.Opened) } },
    { label: 'Download', onClick: () => void download() },
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
    { separator: true as const },
    { label: 'Rename', onClick: () => { setTargetFilePath(item.path); setRenameFileModal(ModalState.Opened) } },
    { label: 'Delete', onClick: () => { setTargetFilePath(item.path); setDeleteFileModal(ModalState.Opened) } },
  ], [item.path, selectedNodes])

  useContextualMenuRegistration(anchorName, entries)

  if (asTile) {
    return (
      <div
        data-selectable
        data-contextual-menu={anchorName}
        style={{ anchorName }}
        className="flex flex-col items-center gap-1 p-3 rounded-lg w-24 select-none"
      >
        <FileIcon size={36} className="text-info" />
        <span className="text-xs text-center truncate w-full">{item.name}</span>
        <span className="text-xs text-base-content/50"><FileSize bytes={item.size} /></span>
      </div>
    )
  }

  return (
    <>
      <div data-selectable data-contextual-menu={anchorName} tabIndex={-1} style={{ anchorName }} className="table-cell select-none p-2">
        <span className="flex items-center gap-2">
          <FileIcon size={16} className="text-info" />
          <span>{item.name}</span>
        </span>
      </div>
      <div className="table-cell select-none p-2"><span className="badge badge-ghost badge-sm">File</span></div>
      <div className="table-cell select-none p-2"><FileSize bytes={item.size} /></div>
      <div className="table-cell select-none p-2">{item.extension ?? <span className="text-base-content/40">N/A</span>}</div>
    </>
  )
}
