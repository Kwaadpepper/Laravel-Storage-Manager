import { useContainer } from "@ts/container";
import { ModalState, useFileManagerStore, useUiStore } from "@ts/stores";
import { isDirectory } from "@ts/types";
import { FolderOpen } from "lucide-react";
import { useEffect, useMemo, useRef } from "react";
import ContentWrapper from "./content/content-wrapper";

interface ContentViewProps {
}

export default function ContentView(_: Readonly<ContentViewProps>) {
  const { directoryNodes, fileNodes, selectedNodes, selectNodes } = useFileManagerStore()
  const { viewMode, setTargetFilePath, setViewFileModal } = useUiStore()
  const container = useContainer()
  const navigationService = container.resolve('navigationService')

  const items = useMemo(() => [...directoryNodes, ...fileNodes], [directoryNodes, fileNodes])
  const isEmpty = items.length === 0
  const listRef = useRef<HTMLDivElement>(null)

  /** Index of the currently focused item for keyboard navigation */
  let rovingIdx: number
  const selectedFile = Object.values(selectedNodes)[0] || null

  if (selectedFile === null) {
    rovingIdx = items.length > 0 ? 0 : -1
  } else {
    rovingIdx = items.findIndex((i) => i.path === selectedFile.path)
  }

  // * SCROLL TO SELECTED ITEM
  useEffect(() => {
    const selectedNodesArray = Object.values(selectedNodes)
    const firstItem = selectedNodesArray.at(0)

    if (selectedNodesArray.length !== 1 || viewMode !== 'list') {
      return
    }

    const el = listRef.current?.querySelector<HTMLElement>(`[data-path="${firstItem!.path}"]`)

    if (el) {
      el.scrollIntoView({ block: 'nearest' })
      el.focus({ preventScroll: true })
    }

  }, [selectedNodes, viewMode])

  // * LIST KEY HANDLER
  function onListKeyDown(e: React.KeyboardEvent<HTMLElement>) {
    if (items.length === 0) {
      return
    }

    const currentIndex = selectedFile
      ? items.findIndex((i) => i.path === selectedFile.path)
      : -1

    switch (e.key) {
      case 'ArrowDown':
        e.preventDefault()
        selectNodes(items[currentIndex < items.length - 1 ? currentIndex + 1 : 0])
        break

      case 'ArrowUp':
        e.preventDefault()
        selectNodes(items[currentIndex > 0 ? currentIndex - 1 : items.length - 1])
        break

      case 'Enter':
        if (selectedFile === null) {
          return
        }

        e.preventDefault()
        e.stopPropagation()

        if (isDirectory(selectedFile)) {
          navigationService.navigateTo(selectedFile.path)
        } else {
          setTargetFilePath(selectedFile.path)
          requestAnimationFrame(() => setViewFileModal(ModalState.Opened))
        }
        break

      case 'Escape':
        e.preventDefault()
        selectNodes()
        break
    }
  }

  return (
    <>
      <div
        ref={listRef}
        className={`overflow-x-auto ${viewMode === 'list' ? '' : 'hidden'}`}
        role="grid"
        aria-label="File list"
        tabIndex={rovingIdx === -1 ? 0 : -1}
        onKeyDown={onListKeyDown}
      >
        <div className="table table-zebra w-full">
          <div className="table-header-group">
            <div className="table-row">
              <div className="table-cell p-2">Name</div>
              <div className="table-cell p-2">Type</div>
              <div className="table-cell p-2">Size</div>
              <div className="table-cell p-2">Extension</div>
            </div>
          </div>
          <div className="table-row-group">
            {items.map((item, i) => (
              <ContentWrapper key={item.path} item={item} tabIndex={rovingIdx === i ? 0 : -1} isListView />
            ))}
          </div>
        </div>

        {isEmpty && (
          <div className="flex flex-col items-center justify-center py-16 text-base-content/40">
            <FolderOpen size={48} className="mb-2" />
            <p>Directory is empty</p>
          </div>
        )}
      </div>
      <div className={`p-2 ${viewMode === 'tiles' ? '' : 'hidden'}`}>
        {isEmpty ? (
          <div className="flex flex-col items-center justify-center py-16 text-base-content/40">
            <FolderOpen size={48} className="mb-2" />
            <p>Directory is empty</p>
          </div>
        ) : (
          <div className="flex flex-wrap gap-2">
            {items.map((item, i) => (
              <ContentWrapper key={item.path} item={item} tabIndex={rovingIdx === i ? 0 : -1} />
            ))}
          </div>
        )}
      </div>
    </>
  );
}
