import { useContainer } from "@ts/container";
import { isDirectory, TreeNode } from "@ts/types";
import { FolderOpen } from "lucide-react";

interface DirectoryButtonsProps {
  selectedNodes: TreeNode[]
}

export default function DirectoryButtons({ selectedNodes }: Readonly<DirectoryButtonsProps>) {
  const container = useContainer()
  const navigationService = container.resolve('navigationService')

  const hasMultipleSelection = selectedNodes.length > 1
  const firstSelectedNode: TreeNode | null = selectedNodes[0] || null
  const firstNodeIsDir = firstSelectedNode !== null && isDirectory(firstSelectedNode)

  function onClickOpen(_: React.MouseEvent<HTMLButtonElement>) {
    if (firstSelectedNode === null || !firstNodeIsDir) {
      return
    }
    navigationService.navigateTo(firstSelectedNode.path)
  }

  if (hasMultipleSelection || !firstNodeIsDir) {
    return null
  }

  return (
    <button
      type="button"
      className="btn btn-ghost btn-xs"
      title="Open"
      onClick={onClickOpen}
    >
      <FolderOpen size={14} />
      <span className="hidden sm:inline">Open</span>
    </button>
  );
}
