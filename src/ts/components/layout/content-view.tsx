import { SortHeader } from "@ts/components/shared/sort-header";
import { useContextualMenuRegistration } from "@ts/components/shared/use-contextual-menu-registration";
import { useContainer } from "@ts/container";
import { ModalState, toAnchorName, useClipboardStore, useFileManagerStore, useUiStore } from "@ts/stores";
import { isDirectory, TreeNode, TreeNodeDirectory, TreeNodeFile } from "@ts/types";
import { SelectionArea, SelectionEvent } from "@viselect/react";
import { FolderOpen } from "lucide-react";
import { useEffect, useMemo, useRef, useState } from "react";
import ContentWrapper from "./content/content-wrapper";

interface ContentViewProps {
}

type SortColumn = 'name' | 'type' | 'size' | 'extension' | 'visibility'

export default function ContentView(_: Readonly<ContentViewProps>) {
  const { directoryNodes, fileNodes, selectedNodes, selectNodes, currentPath } = useFileManagerStore()
  const { viewMode, setTargetFilePath, setViewFileModal } = useUiStore()
  const container = useContainer()
  const navigationService = container.resolve('navigationService')
  const clipboardService = container.resolve('clipboardService')
  const fileManagerService = container.resolve('fileManagerService')
  const toastService = container.resolve('toastService')
  const { hasEntries } = useClipboardStore()

  const [sortColumn, setSortColumn] = useState<SortColumn | null>(null)
  const [sortDirection, setSortDirection] = useState<'asc' | 'desc' | null>(null)

  const items = useMemo(() => {
    if (!sortColumn || !sortDirection) {
      return [...directoryNodes, ...fileNodes]
    }

    const dirFactor = sortDirection === 'asc' ? 1 : -1
    const sortFn = (a: any, b: any) => {
      if (sortColumn === 'name') {
        return a.name.localeCompare(b.name) * dirFactor
      }
      if (sortColumn === 'size') {
        const sizeA = a.size || 0
        const sizeB = b.size || 0
        return (sizeA - sizeB) * dirFactor
      }
      if (sortColumn === 'extension' || sortColumn === 'type') {
        const extA = a.extension || ''
        const extB = b.extension || ''
        return extA.localeCompare(extB) * dirFactor
      }
      if (sortColumn === 'visibility') {
        const visA = a.visibility || ''
        const visB = b.visibility || ''
        return visA.localeCompare(visB) * dirFactor
      }
      return 0
    }

    const dirs = [...directoryNodes].sort(sortFn)
    const files = [...fileNodes].sort(sortFn)
    return [...dirs, ...files]
  }, [directoryNodes, fileNodes, sortColumn, sortDirection])
  const isEmpty = items.length === 0
  const listRef = useRef<HTMLDivElement>(null)
  const [lastSelectedIndex, setLastSelectedIndex] = useState<number | null>(null)
  const dragStartSelectionRef = useRef<TreeNode[]>([])
  const hasDraggedRef = useRef<boolean>(false)

  const handleSort = (column: SortColumn) => {
    if (sortColumn === column) {
      if (sortDirection === 'asc') {
        setSortDirection('desc')
      } else if (sortDirection === 'desc') {
        setSortColumn(null)
        setSortDirection(null)
      }
    } else {
      setSortColumn(column)
      setSortDirection('asc')
    }
  }

  const bgAnchorName = toAnchorName(`bg-${currentPath}`)
  const bgEntries = useMemo(() => [
    ...(hasEntries ? [{
      label: 'Paste', onClick: async () => {
        const isCut = clipboardService.getIsConsumingMode()
        const entry = clipboardService.getLastEntry()
        if (!entry || entry.length === 0) {
          toastService.pushToast({ message: 'Clipboard is empty.', type: 'info' })
          return
        }

        const eventQueueService = container.resolve('eventQueueService')
        const events = entry.map(path => ({
          id: `${isCut ? 'move' : 'copy'}-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`,
          type: (isCut ? 'MOVE' : 'COPY') as 'MOVE' | 'COPY',
          sourcePath: path,
          destinationPath: currentPath,
          execute: async () => {
            if (isCut) {
              await fileManagerService.move(path, currentPath)
            } else {
              await fileManagerService.copy(path, currentPath)
            }
          }
        }))

        eventQueueService.pushBatch(events)
        clipboardService.clearEntries()
      }
    }] : []),
  ], [currentPath, hasEntries])
  useContextualMenuRegistration(bgAnchorName, bgEntries)

  /** Index of the currently focused item for keyboard navigation */
  let rovingIdx: number
  const selectedFile = Object.values(selectedNodes)[0] || null

  if (selectedFile === null) {
    rovingIdx = items.length > 0 ? 0 : -1
  } else {
    rovingIdx = items.findIndex((i) => i.path === selectedFile.path)
  }

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
        setLastSelectedIndex(null)
        break

      case 'a':
      case 'A':
        if (e.ctrlKey || e.metaKey) {
          e.preventDefault()
          selectNodes(...items)
        }
        break
    }
  }

  function handleItemClick(item: TreeNodeDirectory | TreeNodeFile, index: number, e: React.MouseEvent) {
    e.stopPropagation()
    const isMac = navigator.userAgent.includes('Mac')
    const isMultiToggle = isMac ? e.metaKey : e.ctrlKey
    const isRangeSelect = e.shiftKey
    const selectedNodesArray = Object.values(selectedNodes)

    if (isRangeSelect && lastSelectedIndex !== null) {
      const start = Math.min(lastSelectedIndex, index)
      const end = Math.max(lastSelectedIndex, index)
      const range = items.slice(start, end + 1)

      if (isMultiToggle) {
        const newSelection = [...selectedNodesArray]
        for (const node of range) {
          if (!newSelection.some(n => n.path === node.path)) {
            newSelection.push(node)
          }
        }
        selectNodes(...newSelection)
      } else {
        selectNodes(...range)
      }
    } else if (isMultiToggle) {
      const isSelected = selectedNodesArray.some(n => n.path === item.path)
      if (isSelected) {
        selectNodes(...selectedNodesArray.filter(n => n.path !== item.path))
      } else {
        selectNodes(...selectedNodesArray, item)
      }
      setLastSelectedIndex(index)
    } else {
      const isSelected = selectedNodesArray.some(n => n.path === item.path)

      if (isSelected) {
        handleItemDoubleClick(item)
      } else {
        selectNodes(item)
        setLastSelectedIndex(index)
      }
    }
  }

  function handleItemDoubleClick(item: TreeNodeDirectory | TreeNodeFile) {
    if (isDirectory(item)) {
      navigationService.navigateTo(item.path)
    } else {
      setTargetFilePath(item.path)
      setViewFileModal(ModalState.Opened)
    }
  }

  function handleItemContextMenu(item: TreeNodeDirectory | TreeNodeFile) {
    const isSelected = Object.values(selectedNodes).some(n => n.path === item.path)
    if (!isSelected) {
      selectNodes(item)
      const index = items.findIndex((i) => i.path === item.path)
      setLastSelectedIndex(index !== -1 ? index : null)
    }
  }

  function onClickOutside(e: React.MouseEvent<HTMLElement>) {
    if (hasDraggedRef.current) {
      return
    }
    const target = e.target as HTMLElement
    if (target.closest('.selectable')) {
      return
    }
    selectNodes()
    setLastSelectedIndex(null)
  }

  function onBeforeDragStart(e: SelectionEvent): boolean {
    const target = e.event?.target as Element
    if (target?.closest('.selectable')) {
      return false
    }
    return true
  }

  function onDragStart({ event, selection }: SelectionEvent) {
    hasDraggedRef.current = false
    const isMultiToggle = event?.ctrlKey || event?.metaKey || event?.shiftKey
    selection.clearSelection()

    if (isMultiToggle) {
      dragStartSelectionRef.current = Object.values(useFileManagerStore.getState().selectedNodes)
    } else {
      dragStartSelectionRef.current = []
      selectNodes()
      setLastSelectedIndex(null)
    }
  }

  function onDragMove(e: SelectionEvent) {
    hasDraggedRef.current = true
    const isMultiToggle = e.event?.ctrlKey || e.event?.metaKey || e.event?.shiftKey

    const state = useFileManagerStore.getState()
    const currentItems = [...state.directoryNodes, ...state.fileNodes]

    const boxPaths = e.store.selected.map(el => el.getAttribute('data-path')).filter(Boolean) as string[]
    const boxNodes = boxPaths.map(path => currentItems.find(i => i.path === path)).filter(Boolean) as (TreeNodeDirectory | TreeNodeFile)[]

    if (isMultiToggle) {
      const newSelection = [...dragStartSelectionRef.current]
      for (const node of boxNodes) {
        if (!newSelection.some(n => n.path === node.path)) {
          newSelection.push(node)
        }
      }
      selectNodes(...(newSelection as (TreeNodeDirectory | TreeNodeFile)[]))
    } else {
      selectNodes(...boxNodes)
    }
  }

  return (
    <>
      <SelectionArea
        className={`overflow-auto h-full pb-20 ${viewMode === 'list' ? '' : 'hidden'}`}
        onBeforeStart={onBeforeDragStart}
        onStart={onDragStart}
        onMove={onDragMove}
        selectables=".selectable"
      >
        <div
          ref={listRef}
          data-contextual-menu={bgAnchorName}
          style={{ anchorName: bgAnchorName }}
          className="h-full"
          role="grid"
          aria-label="File list"
          tabIndex={rovingIdx === -1 ? 0 : -1}
          onKeyDown={onListKeyDown}
          onClick={onClickOutside}
        >
          <div className="table table-zebra w-full">
            <div className="table-header-group">
              <div className="table-row">
                <SortHeader label="Name" direction={sortColumn === 'name' ? sortDirection : null} onClick={() => handleSort('name')} />
                <SortHeader label="Type" direction={sortColumn === 'type' ? sortDirection : null} onClick={() => handleSort('type')} />
                <SortHeader label="Size" direction={sortColumn === 'size' ? sortDirection : null} onClick={() => handleSort('size')} />
                <SortHeader label="Extension" direction={sortColumn === 'extension' ? sortDirection : null} onClick={() => handleSort('extension')} />
                <SortHeader label="Visibility" direction={sortColumn === 'visibility' ? sortDirection : null} onClick={() => handleSort('visibility')} />
              </div>
            </div>
            <div className="table-row-group">
              {items.map((item, i) => (
                <ContentWrapper
                  key={item.path}
                  item={item}
                  tabIndex={rovingIdx === i ? 0 : -1}
                  isListView
                  onClick={(e) => handleItemClick(item, i, e)}
                  onDoubleClick={() => handleItemDoubleClick(item)}
                  onContextMenu={() => handleItemContextMenu(item)}
                />
              ))}
            </div>
          </div>

          {isEmpty && (
            <div className="flex flex-col items-center justify-center py-16 text-base-content/40 pointer-events-none">
              <FolderOpen size={48} className="mb-2" />
              <p>Directory is empty</p>
            </div>
          )}
        </div>
      </SelectionArea>
      <SelectionArea
        className={`p-2 overflow-y-auto h-full ${viewMode === 'tiles' ? '' : 'hidden'}`}
        onBeforeStart={onBeforeDragStart}
        onStart={onDragStart}
        onMove={onDragMove}
        selectables=".selectable"
      >
        <div
          data-contextual-menu={bgAnchorName}
          style={{ anchorName: bgAnchorName }}
          className="h-full min-h-[50vh]"
          role="grid"
          aria-label="File tiles"
          tabIndex={rovingIdx === -1 ? 0 : -1}
          onKeyDown={onListKeyDown}
          onClick={onClickOutside}
        >
          {isEmpty ? (
            <div className="flex flex-col items-center justify-center py-16 text-base-content/40 pointer-events-none">
              <FolderOpen size={48} className="mb-2" />
              <p>Directory is empty</p>
            </div>
          ) : (
            <div className="flex flex-wrap gap-2">
              {items.map((item, i) => (
                <ContentWrapper
                  key={item.path}
                  item={item}
                  tabIndex={rovingIdx === i ? 0 : -1}
                  onClick={(e) => handleItemClick(item, i, e)}
                  onDoubleClick={() => handleItemDoubleClick(item)}
                  onContextMenu={() => handleItemContextMenu(item)}
                />
              ))}
            </div>
          )}
        </div>
      </SelectionArea>
    </>
  );
}
