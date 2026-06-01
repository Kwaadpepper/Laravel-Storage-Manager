import { useContainer } from "@ts/container";
import { ModalState, useUiStore } from "@ts/stores";
import { isDirectory, TreeNodeFile } from "@ts/types";
import { Download, Eye } from "lucide-react";

interface FileButtonsProps {
  selectedFile: TreeNodeFile | null
}

export default function FileButtons({ selectedFile }: Readonly<FileButtonsProps>) {
  const { setTargetFilePath, setViewFileModal } = useUiStore()
  const container = useContainer()
  const downloadService = container.resolve('downloadService')
  const toastService = container.resolve('toastService')

  const isDir = selectedFile !== null && isDirectory(selectedFile)

  function onClickView(_: React.MouseEvent<HTMLButtonElement>) {
    if (selectedFile === null || isDir) {
      return
    }
    setTargetFilePath(selectedFile.path)
    setViewFileModal(ModalState.Opened)
  }

  async function onClickDownload(_: React.MouseEvent<HTMLButtonElement>) {
    try {
      if (selectedFile === null || isDir) {
        return
      }
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

  return (
    <>
      <button
        type="button"
        className="btn btn-ghost btn-xs"
        title="View"
        onClick={onClickView}
      >
        <Eye size={14} />
        <span className="hidden sm:inline">View</span>
      </button>
      <button
        type="button"
        className="btn btn-ghost btn-xs"
        title="Download"
        onClick={onClickDownload}
      >
        <Download size={14} />
        <span className="hidden sm:inline">Download</span>
      </button>
    </>
  );
}
