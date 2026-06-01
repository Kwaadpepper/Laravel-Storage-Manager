import { ModalState, useUiStore } from "@ts/stores";
import { isDirectory, TreeNode } from "@ts/types";
import { Pencil, Trash2 } from "lucide-react";

interface CommonButtonsProps {
  selectedFile: TreeNode | null
}

export default function CommonButtons({ selectedFile }: Readonly<CommonButtonsProps>) {
  const { setRenameFileModal, setDeleteFileModal, setTargetFilePath,
    setRenameDirectoryModal, setDeleteDirectoryModal, setTargetDirectoryPath } = useUiStore()

  const isDir = selectedFile !== null && isDirectory(selectedFile)

  function onClickRename(_: React.MouseEvent<HTMLButtonElement>) {
    if (selectedFile === null) {
      return
    }
    if (isDir) {
      setTargetDirectoryPath(selectedFile.path)
      setRenameDirectoryModal(ModalState.Opened)
    } else {
      setTargetFilePath(selectedFile.path)
      setRenameFileModal(ModalState.Opened)
    }
  }

  function onClickDelete(_: React.MouseEvent<HTMLButtonElement>) {
    if (selectedFile === null) {
      return
    }
    if (isDir) {
      setTargetDirectoryPath(selectedFile.path)
      setDeleteDirectoryModal(ModalState.Opened)
    } else {
      setTargetFilePath(selectedFile.path)
      setDeleteFileModal(ModalState.Opened)
    }
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
