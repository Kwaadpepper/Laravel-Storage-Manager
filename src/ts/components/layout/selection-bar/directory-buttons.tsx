import { useContainer } from "@ts/container";
import { isDirectory, TreeNodeDirectory } from "@ts/types";
import { FolderOpen } from "lucide-react";

interface DirectoryButtonsProps {
  selectedFile: TreeNodeDirectory | null
}

export default function DirectoryButtons({ selectedFile }: Readonly<DirectoryButtonsProps>) {
  const container = useContainer()
  const navigationService = container.resolve('navigationService')

  const isDir = selectedFile !== null && isDirectory(selectedFile)

  function onClickOpen(_: React.MouseEvent<HTMLButtonElement>) {
    if (selectedFile === null || !isDir) {
      return
    }
    navigationService.navigateTo(selectedFile.path)
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
