import { useContainer } from "@ts/container";
import { ModalState, useFileManagerStore, useUiStore } from "@ts/stores";
import { isDirectory, Path } from "@ts/types";
import { FolderOpen } from "lucide-react";
import { useEffect, useMemo, useRef } from "react";
import ContentTileDirectory from "./content/content-tile-directory";
import ContentTileFile from "./content/content-tile-file";
import ContentWrapper from "./content/content-wrapper";

interface ContentViewProps {
}

export default function ContentView(_: Readonly<ContentViewProps>) {
  const { directories, files, selectedFile, selectNode } = useFileManagerStore()
  const { viewMode, setTargetFilePath, setViewFileModal } = useUiStore()
  const container = useContainer()
  const navigationService = container.resolve('navigationService')

  const items = useMemo(() => [...directories, ...files], [directories, files])
  const isEmpty = items.length === 0
  const listRef = useRef<HTMLDivElement>(null)

  let rovingIdx: number
  if (selectedFile === null) {
    rovingIdx = items.length > 0 ? 0 : -1
  } else {
    rovingIdx = items.findIndex((i) => i.path === selectedFile.path)
  }

  useEffect(() => {
    if (!selectedFile || viewMode !== 'list') return
    const el = listRef.current?.querySelector<HTMLElement>(`[data-path="${selectedFile.path}"]`)
    if (el) {
      el.scrollIntoView({ block: 'nearest' })
      el.focus({ preventScroll: true })
    }
  }, [selectedFile, viewMode])

  useEffect(() => {
    function onDocKeyDown(e: KeyboardEvent) {
      const tag = (e.target as HTMLElement).tagName
      if (tag === 'INPUT' || tag === 'TEXTAREA' || (e.target as HTMLElement).isContentEditable) return
      if (e.key === 'Backspace') {
        e.preventDefault()
        navigationService.navigatePrevious()
      }
    }
    document.addEventListener('keydown', onDocKeyDown)
    return () => document.removeEventListener('keydown', onDocKeyDown)
  }, [navigationService])

  function handleListKeyDown(e: React.KeyboardEvent) {
    if (items.length === 0) return
    const currentIndex = selectedFile
      ? items.findIndex((i) => i.path === selectedFile.path)
      : -1

    if (e.key === 'ArrowDown') {
      e.preventDefault()
      selectNode(currentIndex < items.length - 1 ? items[currentIndex + 1] : items[0])
    } else if (e.key === 'ArrowUp') {
      e.preventDefault()
      selectNode(currentIndex > 0 ? items[currentIndex - 1] : items.at(-1)!)
    } else if (e.key === 'Enter' && selectedFile !== null) {
      e.preventDefault()
      e.stopPropagation()
      if (isDirectory(selectedFile)) {
        navigationService.navigateTo(selectedFile.path)
      } else {
        setTargetFilePath(selectedFile.path as Path)
        requestAnimationFrame(() => setViewFileModal(ModalState.Opened))
      }
    } else if (e.key === 'Escape') {
      e.preventDefault()
      selectNode(null)
    }
  }

  if (viewMode === 'tiles') {
    return (
      <div className="p-2">
        {isEmpty ? (
          <div className="flex flex-col items-center justify-center py-16 text-base-content/40">
            <FolderOpen size={48} className="mb-2" />
            <p>Directory is empty</p>
          </div>
        ) : (
          <div className="flex flex-wrap gap-2">
            {directories.map((d) => (
              <ContentTileDirectory key={d.path} item={d} />
            ))}
            {files.map((f) => (
              <ContentTileFile key={f.path} item={f} />
            ))}
          </div>
        )}
      </div>
    )
  }

  return (
    <div
      ref={listRef}
      className="overflow-x-auto"
      role="grid"
      aria-label="File list"
      tabIndex={rovingIdx === -1 ? 0 : -1}
      onKeyDown={handleListKeyDown}
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
            <ContentWrapper key={item.path} item={item} tabIndex={rovingIdx === i ? 0 : -1} />
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
  );
}
