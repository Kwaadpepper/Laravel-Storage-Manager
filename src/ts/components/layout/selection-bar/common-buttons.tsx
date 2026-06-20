import { ModalState, useUiStore } from "@ts/stores";
import { isDirectory, TreeNode } from "@ts/types";
import { CopyPlus, FolderOutput, Pencil, Trash2 } from "lucide-react";

interface CommonButtonsProps {
  selectedNodes: TreeNode[]
}

export default function CommonButtons({ selectedNodes }: Readonly<CommonButtonsProps>) {
  const hasMultipleSelection = selectedNodes.length > 1
  const firstSelectedNode: TreeNode | null = selectedNodes[0] || null
  const firstNodeIsDirectory = firstSelectedNode !== null && isDirectory(firstSelectedNode)

  const { setRenameFileModal, setDeleteModal, setTargetFilePath,
    setRenameDirectoryModal, setTargetDirectoryPath,
    setMoveModal, setCopyModal } = useUiStore()

  function onClickRename(_: React.MouseEvent<HTMLButtonElement>) {
    if (selectedNodes.length !== 1) {
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
    setDeleteModal(ModalState.Opened)
  }

  if (selectedNodes.length === 0) {
    return null
  }

  return (
    <>
      <button
        type="button"
        className="btn btn-ghost btn-xs"
        title="Copy To"
        onClick={() => setCopyModal(ModalState.Opened)}
      >
        <CopyPlus size={14} />
        <span className="hidden sm:inline">Copy To</span>
      </button>

      <button
        type="button"
        className="btn btn-ghost btn-xs"
        title="Move To"
        onClick={() => setMoveModal(ModalState.Opened)}
      >
        <FolderOutput size={14} />
        <span className="hidden sm:inline">Move To</span>
      </button>

      <button
        type="button"
        className="btn btn-ghost btn-xs"
        title="Rename"
        onClick={onClickRename}
        disabled={hasMultipleSelection}
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
