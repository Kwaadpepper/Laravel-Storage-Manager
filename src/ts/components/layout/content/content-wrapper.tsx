import { useContainer } from "@ts/container";
import { ModalState, useFileManagerStore, useUiStore } from "@ts/stores";
import { isDirectory, TreeNodeDirectory, TreeNodeFile } from "@ts/types";
import ContentDirectory from "./content-directory";
import ContentFile from "./content-file";

type Item = TreeNodeDirectory | TreeNodeFile

interface ContentWrapperProps {
  readonly item: Item
  readonly tabIndex?: number
}

export default function ContentWrapper({ item, tabIndex = -1 }: Readonly<ContentWrapperProps>) {
  const container = useContainer()
  const navigationService = container.resolve('navigationService')
  const { setTargetFilePath, setViewFileModal } = useUiStore()
  const { selectNode, selectedFile } = useFileManagerStore()

  const isSelected = selectedFile?.path === item.path

  function onItemClick(item: Item) {
    if (isDirectory(item)) {
      navigationService.navigateTo(item.path)
    } else {
      selectNode(item)
    }
  }

  function onItemDoubleClick(item: Item) {
    if (!isDirectory(item)) {
      setTargetFilePath(item.path)
      setViewFileModal(ModalState.Opened)
    }
  }

  return (
    <div
      className={`table-row hover:cursor-pointer hover:bg-accent/30${isSelected ? ' bg-primary/20' : ''}`}
      title={item.name}
      role="row"
      aria-selected={isSelected}
      tabIndex={tabIndex}
      data-path={item.path}
      onClick={() => onItemClick(item)}
      onDoubleClick={() => onItemDoubleClick(item)}
      onKeyDown={(e) => {
        if (e.key === ' ' && !isDirectory(item)) { e.preventDefault(); selectNode(item) }
        if (e.key === 'Enter' && isDirectory(item)) navigationService.navigateTo(item.path)
      }}
    >
      {isDirectory(item) ? (
        <ContentDirectory item={item} />
      ) : (
        <ContentFile item={item} />
      )}
    </div>
  )
}
