import { ModalState, useConfigStore, useDiskStore, useUiStore } from "@ts/stores";
import { isDirectory, TreeNode } from "@ts/types";
import { CopyPlus, FolderOutput, Link, Pencil, Trash2 } from "lucide-react";
import { useContainer } from "@ts/container";

interface CommonButtonsProps {
  selectedNodes: TreeNode[]
}

export default function CommonButtons({ selectedNodes }: Readonly<CommonButtonsProps>) {
  const hasMultipleSelection = selectedNodes.length > 1
  const firstSelectedNode: TreeNode | null = selectedNodes[0] || null
  const firstNodeIsDirectory = firstSelectedNode !== null && isDirectory(firstSelectedNode)

  const { readOnlyDisks } = useConfigStore()
  const { currentDisk } = useDiskStore()
  const isReadOnly = currentDisk ? readOnlyDisks.includes(currentDisk) : false

  const { setRenameFileModal, setDeleteModal, setTargetFilePath,
    setRenameDirectoryModal, setTargetDirectoryPath,
    setMoveModal, setCopyModal } = useUiStore()

  const container = useContainer()
  const toastService = container.resolve('toastService')

  function onCopyPathClick() {
    const paths = selectedNodes.map(n => n.path).join('\n')
    navigator.clipboard.writeText(paths).catch(() => {})
    toastService.pushToast({ message: 'Path(s) copied to clipboard.', type: 'success' })
  }

  function onCopyLinkClick() {
    const url = new URL(globalThis.location.href)
    const disk = currentDisk
    const links = selectedNodes.map(n => {
      const hashPath = `/${disk}${n.path}`
      url.hash = hashPath.split('/').map(encodeURIComponent).join('/')
      return url.toString()
    }).join('\n')
    navigator.clipboard.writeText(links).catch(() => {})
    toastService.pushToast({ message: 'Link(s) copied to clipboard.', type: 'success' })
  }

  function onCopyPublicUrlClick() {
    const urls = selectedNodes.map(n => (n as TreeNode & { publicUrl?: string }).publicUrl).filter(Boolean).join('\n')
    if (urls) {
      navigator.clipboard.writeText(urls).catch(() => {})
      toastService.pushToast({ message: 'Public URL(s) copied to clipboard.', type: 'success' })
    } else {
      toastService.pushToast({ message: 'No public URL found.', type: 'warning' })
    }
  }

  function onClickRename(_: React.MouseEvent<HTMLButtonElement>) {
    if (selectedNodes.length !== 1 || !firstSelectedNode) {
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
        disabled={isReadOnly}
      >
        <CopyPlus size={14} />
        <span className="hidden sm:inline">Copy To</span>
      </button>

      <button
        type="button"
        className="btn btn-ghost btn-xs"
        title="Move To"
        onClick={() => setMoveModal(ModalState.Opened)}
        disabled={isReadOnly}
      >
        <FolderOutput size={14} />
        <span className="hidden sm:inline">Move To</span>
      </button>

      <button
        type="button"
        className="btn btn-ghost btn-xs"
        title="Rename"
        onClick={onClickRename}
        disabled={hasMultipleSelection || isReadOnly}
      >
        <Pencil size={14} />
        <span className="hidden sm:inline">Rename</span>
      </button>

      <button
        type="button"
        className="btn btn-ghost btn-xs text-error"
        title="Delete"
        onClick={onClickDelete}
        disabled={isReadOnly}
      >
        <Trash2 size={14} />
        <span className="hidden sm:inline">Delete</span>
      </button>

      <div className="dropdown dropdown-bottom">
        <div tabIndex={0} role="button" className="btn btn-ghost btn-xs" title="Copy Links">
          <Link size={14} />
          <span className="hidden sm:inline">URL</span>
        </div>
        <ul tabIndex={0} className="dropdown-content z-[50] menu p-2 shadow bg-base-100 rounded-box w-52">
          <li><a onClick={onCopyPathClick}>Copy Path</a></li>
          <li><a onClick={onCopyLinkClick}>Copy Link</a></li>
          {selectedNodes.some(n => (n as TreeNode & { publicUrl?: string }).publicUrl) && (
            <li><a onClick={onCopyPublicUrlClick}>Copy Public URL</a></li>
          )}
        </ul>
      </div>
    </>
  );
}
