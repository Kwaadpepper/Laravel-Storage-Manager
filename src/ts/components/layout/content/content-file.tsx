import { useContextualMenuRegistration } from "@ts/components/shared/use-contextual-menu-registration";
import { useContainer } from "@ts/container";
import { ModalState, toAnchorName, useUiStore } from "@ts/stores";
import { TreeNodeFile } from "@ts/types";
import { FileIcon } from "lucide-react";
import { useMemo } from "react";

interface ContentFileProps {
  readonly item: TreeNodeFile
}

export default function ContentFile({ item }: Readonly<ContentFileProps>) {
  const { setRenameFileModal, setDeleteFileModal, setTargetFilePath } = useUiStore()
  const container = useContainer()
  const downloadService = container.resolve('downloadService')
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
    { label: 'Download', onClick: () => void download() },
    { label: 'Rename', onClick: () => { setTargetFilePath(item.path); setRenameFileModal(ModalState.Opened) } },
    { label: 'Delete', onClick: () => { setTargetFilePath(item.path); setDeleteFileModal(ModalState.Opened) } },
  ], [item.path])

  useContextualMenuRegistration(anchorName, entries)

  return (
    <>
      <div data-contextual-menu={anchorName} tabIndex={-1} style={{ anchorName }} className="table-cell p-2">
        <span className="flex items-center gap-2">
          <FileIcon size={16} className="text-info" />
          <span>{item.name}</span>
        </span>
      </div>
      <div className="table-cell p-2"><span className="badge badge-ghost badge-sm">File</span></div>
      <div className="table-cell p-2">{item.size} o</div>
      <div className="table-cell p-2">{item.extension ?? <span className="text-base-content/40">N/A</span>}</div>
    </>
  )
}
