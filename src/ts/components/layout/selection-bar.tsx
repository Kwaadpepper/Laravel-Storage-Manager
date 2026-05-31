import { useContainer } from "@ts/container";
import { ModalState, useFileManagerStore, useUiStore } from "@ts/stores";
import { isDirectory } from "@ts/types";
import { Download, Eye, FolderOpen, Pencil, Trash2, X } from "lucide-react";

export default function SelectionBar() {
  const { selectedFile, selectNode } = useFileManagerStore()
  const { setViewFileModal, setRenameFileModal, setDeleteFileModal, setTargetFilePath,
    setRenameDirectoryModal, setDeleteDirectoryModal, setTargetDirectoryPath } = useUiStore()
  const container = useContainer()
  const navigationService = container.resolve('navigationService')
  const downloadService = container.resolve('downloadService')
  const toastService = container.resolve('toastService')

  if (selectedFile === null) return null

  async function download() {
    if (selectedFile === null || isDirectory(selectedFile)) return
    try {
      const blob = await downloadService.downloadFile(selectedFile.path)
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = selectedFile.name
      a.click()
      URL.revokeObjectURL(url)
    } catch {
      toastService.pushToast({ message: 'Failed to download file.', type: 'error' })
    }
  }

  const isDir = isDirectory(selectedFile)

  return (
    <div className="flex items-center gap-2 px-4 py-1 bg-primary/10 border-b border-primary/20">
      <span className="text-sm font-medium text-base-content/70 truncate max-w-xs" title={selectedFile.name}>
        {selectedFile.name}
      </span>

      <div className="flex items-center gap-1 ml-2">
        {isDir ? (
          <button
            type="button"
            className="btn btn-ghost btn-xs"
            title="Open"
            onClick={() => navigationService.navigateTo(selectedFile.path)}
          >
            <FolderOpen size={14} />
            <span className="hidden sm:inline">Open</span>
          </button>
        ) : (
          <>
            <button
              type="button"
              className="btn btn-ghost btn-xs"
              title="View"
              onClick={() => { setTargetFilePath(selectedFile.path); setViewFileModal(ModalState.Opened) }}
            >
              <Eye size={14} />
              <span className="hidden sm:inline">View</span>
            </button>
            <button
              type="button"
              className="btn btn-ghost btn-xs"
              title="Download"
              onClick={() => void download()}
            >
              <Download size={14} />
              <span className="hidden sm:inline">Download</span>
            </button>
          </>
        )}

        <button
          type="button"
          className="btn btn-ghost btn-xs"
          title="Rename"
          onClick={() => {
            if (isDir) {
              setTargetDirectoryPath(selectedFile.path)
              setRenameDirectoryModal(ModalState.Opened)
            } else {
              setTargetFilePath(selectedFile.path)
              setRenameFileModal(ModalState.Opened)
            }
          }}
        >
          <Pencil size={14} />
          <span className="hidden sm:inline">Rename</span>
        </button>

        <button
          type="button"
          className="btn btn-ghost btn-xs text-error"
          title="Delete"
          onClick={() => {
            if (isDir) {
              setTargetDirectoryPath(selectedFile.path)
              setDeleteDirectoryModal(ModalState.Opened)
            } else {
              setTargetFilePath(selectedFile.path)
              setDeleteFileModal(ModalState.Opened)
            }
          }}
        >
          <Trash2 size={14} />
          <span className="hidden sm:inline">Delete</span>
        </button>
      </div>

      <button
        type="button"
        className="btn btn-ghost btn-xs ml-auto"
        title="Deselect"
        onClick={() => selectNode(null)}
      >
        <X size={14} />
      </button>
    </div>
  )
}
