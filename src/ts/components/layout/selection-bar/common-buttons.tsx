import { ModalState, useUiStore } from "@ts/stores";
import { isDirectory, TreeNode } from "@ts/types";
import { Pencil, Trash2 } from "lucide-react";

interface CommonButtonsProps {
  selectedNodes: TreeNode[]
}

export default function CommonButtons({ selectedNodes }: Readonly<CommonButtonsProps>) {
  const hasMultipleSelection = selectedNodes.length > 1
  const firstSelectedNode: TreeNode | null = selectedNodes[0] || null
  const firstNodeIsDirectory = firstSelectedNode !== null && isDirectory(firstSelectedNode)

  const { setRenameFileModal, setDeleteFileModal, setTargetFilePath,
    setRenameDirectoryModal, setDeleteDirectoryModal, setTargetDirectoryPath } = useUiStore()

  function onClickRename(_: React.MouseEvent<HTMLButtonElement>) {
    if (firstSelectedNode === null) {
      return
    }
    if (firstNodeIsDirectory) {
      setTargetDirectoryPath(firstSelectedNode.path)
      setRenameDirectoryModal(ModalState.Opened)
    } else {
      setTargetFilePath(firstSelectedNode.path)
      setRenameFileModal(ModalState.Opened)
    }
  }

  function onClickDelete(_: React.MouseEvent<HTMLButtonElement>) {
    if (firstSelectedNode === null) {
      return
    }
    if (firstNodeIsDirectory) {
      setTargetDirectoryPath(firstSelectedNode.path)
      setDeleteDirectoryModal(ModalState.Opened)
    } else {
      setTargetFilePath(firstSelectedNode.path)
      setDeleteFileModal(ModalState.Opened)
    }
  }

  if (hasMultipleSelection || selectedNodes.length === 0) {
    return null
  }

  return (
    <>
      <button
        type="button"
        className="btn btn-ghost btn-xs"
        title="Rename"
        onClick={onClickRename}
      >
        <Pencil size={14} />
        <span className="hidden sm:inline">Rename</span>
      </button>

      <button
        type="button"
        className="btn btn-ghost btn-xs text-error"
        title="Delete"
        onClick={onClickDelete}
      >
        <Trash2 size={14} />
        <span className="hidden sm:inline">Delete</span>
      </button>
    </>
  );
}
