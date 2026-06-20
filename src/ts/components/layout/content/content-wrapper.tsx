import { useFileManagerStore } from "@ts/stores";
import { isDirectory, TreeNodeDirectory, TreeNodeFile } from "@ts/types";
import { useMemo } from "react";
import ContentDirectory from "./content-directory";
import ContentFile from "./content-file";

type Item = TreeNodeDirectory | TreeNodeFile

interface ContentWrapperProps {
  readonly isListView?: boolean
  readonly item: Item
  readonly tabIndex?: number
  readonly onClick?: (e: React.MouseEvent<HTMLElement>) => void
  readonly onDoubleClick?: (e: React.MouseEvent<HTMLElement>) => void
  readonly onContextMenu?: (e: React.MouseEvent<HTMLElement>) => void
}

export default function ContentWrapper({ isListView = false, item, tabIndex = -1, onClick, onDoubleClick, onContextMenu }: Readonly<ContentWrapperProps>) {
  const { selectedNodes } = useFileManagerStore()
  const isSelected = useMemo(() => Object.values(selectedNodes).some(node => node.path === item.path), [selectedNodes, item.path])

  if (isListView) {
    return (
      <div
        className={`table-row selectable hover:cursor-pointer hover:bg-accent/30 ${isSelected ? 'bg-primary/20 ring-1 ring-primary' : ''}`}
        title={item.name}
        role="row"
        aria-selected={isSelected}
        tabIndex={tabIndex}
        data-path={item.path}
        onClick={onClick}
        onDoubleClick={onDoubleClick}
        onContextMenu={onContextMenu}
      >
        {isDirectory(item) ? (
          <ContentDirectory item={item} />
        ) : (
          <ContentFile item={item} />
        )}
      </div>
    )
  }

  return (
    <button
      className={`selectable hover:cursor-pointer rounded-lg hover:bg-accent/30 ${isSelected ? 'bg-primary/20 ring-1 ring-primary' : ''}`}
      title={item.name}
      aria-pressed={isSelected}
      tabIndex={tabIndex}
      data-path={item.path}
      onClick={onClick}
      onDoubleClick={onDoubleClick}
      onContextMenu={onContextMenu}
    >
      {isDirectory(item) ? (
        <ContentDirectory item={item} asTile />
      ) : (
        <ContentFile item={item} asTile />
      )}
    </button>
  )
}
