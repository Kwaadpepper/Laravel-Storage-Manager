import { useContextualMenuRegistration } from "@ts/components/shared/use-contextual-menu-registration";
import { useContainer } from "@ts/container";
import { useClipboardStore, ModalState, toAnchorName, useFileManagerStore, useUiStore } from "@ts/stores";
import { SelectionArea, SelectionEvent } from "@viselect/react";
import { isDirectory, TreeNodeDirectory, TreeNodeFile, TreeNode } from "@ts/types";
import { FolderOpen } from "lucide-react";
import { useEffect, useMemo, useRef, useState } from "react";
import ContentWrapper from "./content/content-wrapper";

interface ContentViewProps {
}

export default function ContentView(_: Readonly<ContentViewProps>) {
  const { directoryNodes, fileNodes, selectedNodes, selectNodes, currentPath } = useFileManagerStore()
  const { viewMode, setTargetFilePath, setViewFileModal } = useUiStore()
  const container = useContainer()
  const navigationService = container.resolve('navigationService')
  const clipboardService = container.resolve('clipboardService')
  const fileManagerService = container.resolve('fileManagerService')
  const toastService = container.resolve('toastService')
  const { hasEntries } = useClipboardStore()

  const items = useMemo(() => [...directoryNodes, ...fileNodes], [directoryNodes, fileNodes])
  const isEmpty = items.length === 0
  const listRef = useRef<HTMLDivElement>(null)
  const [lastSelectedIndex, setLastSelectedIndex] = useState<number | null>(null)
  const dragStartSelectionRef = useRef<TreeNode[]>([])
  const hasDraggedRef = useRef<boolean>(false)

  // Background contextual menu (right-click on empty space)
  const bgAnchorName = toAnchorName(`bg-${currentPath}`)
  const bgEntries = useMemo(() => [
    ...(hasEntries ? [{ label: 'Paste', onClick: async () => {
      const isCut = clipboardService.getIsConsumingMode()
      const entry = clipboardService.getLastEntry()
      if (!entry || entry.length === 0) {
        toastService.pushToast({ message: 'Clipboard is empty.', type: 'info' })
        return
      }
      try {
        for (const path of entry) {
          if (isCut) {
            await fileManagerService.move(path, currentPath)
          } else {
            await fileManagerService.copy(path, currentPath)
          }
        }
        clipboardService.clearEntries()
        toastService.pushToast({ message: 'Pasted successfully.', type: 'success' })
        await navigationService.refreshCurrentPath()
      } catch (e: any) {
        toastService.pushToast({ message: e.message || 'Failed to paste.', type: 'error' })
      }
    }}] : []),
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
      // Range select
      const start = Math.min(lastSelectedIndex, index)
      const end = Math.max(lastSelectedIndex, index)
      const range = items.slice(start, end + 1)
      
      // If ctrl is also held, add to selection, otherwise replace
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
      // Toggle single
      const isSelected = selectedNodesArray.some(n => n.path === item.path)
      if (isSelected) {
        selectNodes(...selectedNodesArray.filter(n => n.path !== item.path))
      } else {
        selectNodes(...selectedNodesArray, item)
      }
      setLastSelectedIndex(index)
    } else {
      // Normal click
      const isSelected = selectedNodesArray.some(n => n.path === item.path)
      
      if (isSelected) {
        // Trigger primary action if already selected
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
      // It was a drag, ignore this click
      return
    }
    const target = e.target as HTMLElement
    // Ignore if clicking on a selectable item or contextual menu
    if (target.closest('.selectable')) {
      return
    }
    selectNodes()
    setLastSelectedIndex(null)
  }

  // * DRAG SELECTION HANDLERS

  function onBeforeDragStart(e: SelectionEvent): boolean {
    const target = e.event?.target as Element
    // Prevent drag selection if clicking on an interactive element like a file/folder or a button.
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
      selectNodes(...newSelection)
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
              <div className="table-cell p-2 sticky top-0 bg-base-100 z-10 border-b border-base-200">Name</div>
              <div className="table-cell p-2 sticky top-0 bg-base-100 z-10 border-b border-base-200">Type</div>
              <div className="table-cell p-2 sticky top-0 bg-base-100 z-10 border-b border-base-200">Size</div>
              <div className="table-cell p-2 sticky top-0 bg-base-100 z-10 border-b border-base-200">Extension</div>
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
