import FileSize from "@ts/components/shared/file-size";
import { useContextualMenuRegistration } from "@ts/components/shared/use-contextual-menu-registration";
import { useContainer } from "@ts/container";
import { ModalState, toAnchorName, useFileManagerStore, useUiStore } from "@ts/stores";
import { TreeNodeFile } from "@ts/types";
import { FileIcon } from "lucide-react";
import { useMemo } from "react";

interface ContentTileFileProps {
  readonly item: TreeNodeFile
}

export default function ContentTileFile({ item }: Readonly<ContentTileFileProps>) {
  const { setRenameFileModal, setDeleteFileModal, setViewFileModal, setTargetFilePath } = useUiStore()
  const { selectNode, selectedNode: selectedFile } = useFileManagerStore()
  const container = useContainer()
  const downloadService = container.resolve('downloadService')
  const toastService = container.resolve('toastService')
  const anchorName = toAnchorName(item.path)

  const isSelected = selectedFile?.path === item.path

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
    { label: 'Rename', onClick: () => { setTargetFilePath(item.path); setRenameFileModal(ModalState.Opened) } },
    { label: 'Delete', onClick: () => { setTargetFilePath(item.path); setDeleteFileModal(ModalState.Opened) } },
  ], [item.path])

  useContextualMenuRegistration(anchorName, entries)

  return (
    <button
      type="button"
      data-contextual-menu={anchorName}
      style={{ anchorName }}
      className={`flex flex-col items-center gap-1 p-3 rounded-lg hover:bg-accent/30 hover:cursor-pointer w-24${isSelected ? ' bg-primary/20 ring-1 ring-primary' : ''}`}
      title={item.name}
      onClick={() => { selectNode(item) }}
      onDoubleClick={() => { setTargetFilePath(item.path); setViewFileModal(ModalState.Opened) }}
      onContextMenu={() => selectNode(item)}
      onKeyDown={(e) => {
        if (e.key === 'Enter') {
          e.preventDefault()
          setTargetFilePath(item.path)
          requestAnimationFrame(() => setViewFileModal(ModalState.Opened))
        }
      }}
    >
      <FileIcon size={36} className="text-info" />
      <span className="text-xs text-center truncate w-full">{item.name}</span>
      <span className="text-xs text-base-content/50"><FileSize bytes={item.size} /></span>
    </button>
  )
}
