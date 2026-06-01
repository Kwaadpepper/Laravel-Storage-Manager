import { useContainer } from "@ts/container";
import { ModalState, useUiStore } from "@ts/stores";
import { isFile, TreeNode } from "@ts/types";
import { Download, Eye } from "lucide-react";

interface FileButtonsProps {
  selectedNodes: TreeNode[]
}

export default function FileButtons({ selectedNodes }: Readonly<FileButtonsProps>) {
  const hasMultipleSelection = selectedNodes.length > 1
  const firstSelectedNode: TreeNode | null = selectedNodes[0] || null
  const firstNodeIsFile = firstSelectedNode !== null && isFile(firstSelectedNode)

  const { setTargetFilePath, setViewFileModal } = useUiStore()
  const container = useContainer()
  const downloadService = container.resolve('downloadService')
  const toastService = container.resolve('toastService')

  function onClickView(_: React.MouseEvent<HTMLButtonElement>) {
    if (firstSelectedNode === null || !firstNodeIsFile) {
      return
    }
    setTargetFilePath(firstSelectedNode.path)
    setViewFileModal(ModalState.Opened)
  }

  async function onClickDownload(_: React.MouseEvent<HTMLButtonElement>) {
    try {
      if (firstSelectedNode === null || !firstNodeIsFile) {
        return
      }
      const blob = await downloadService.downloadFile(firstSelectedNode.path)
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = firstSelectedNode.name
      a.click()
      URL.revokeObjectURL(url)
    } catch {
      toastService.pushToast({ message: 'Failed to download file.', type: 'error' })
    }
  }

  if (hasMultipleSelection || !firstNodeIsFile) {
    return null
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
