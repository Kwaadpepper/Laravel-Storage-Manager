import { useContainer } from "@ts/container";
import { ModalState, useFileManagerStore, useUiStore } from "@ts/stores";
import { isDirectory, TreeNodeDirectory, TreeNodeFile } from "@ts/types";
import { useEffect, useMemo, useState } from "react";
import ContentDirectory from "./content-directory";
import ContentFile from "./content-file";

type Item = TreeNodeDirectory | TreeNodeFile

interface ContentWrapperProps {
  readonly isListView?: boolean
  readonly item: Item
  readonly tabIndex?: number
}

export default function ContentWrapper({ isListView = false, item, tabIndex = -1 }: Readonly<ContentWrapperProps>) {
  const container = useContainer()
  const navigationService = container.resolve('navigationService')
  const { setTargetFilePath, setViewFileModal } = useUiStore()
  const { selectNodes, selectedNodes } = useFileManagerStore()

  const isSelected = useMemo(() => Object.values(selectedNodes).some(node => node.path === item.path), [selectedNodes, item.path])
  const selectedNodeList = useMemo(() => Object.values(selectedNodes), [selectedNodes])
  const [mouseHolded, setMouseHolded] = useState(false)
  const [majKeyHolded, setMajKeyHolded] = useState(false)

  useEffect(() => {
    function onMouseDown() {
      setMouseHolded(true)
    }

    function onMouseUp() {
      setMouseHolded(false)
    }

    globalThis.addEventListener('mousedown', onMouseDown)
    globalThis.addEventListener('mouseup', onMouseUp)

    return () => {
      globalThis.removeEventListener('mousedown', onMouseDown)
      globalThis.removeEventListener('mouseup', onMouseUp)
    }
  }, [])

  useEffect(() => {
    function onKeyDown(e: KeyboardEvent) {
      if (e.key === 'Shift') {
        setMajKeyHolded(true)
      }
    }

    function onKeyUp(e: KeyboardEvent) {
      if (e.key === 'Shift') {
        setMajKeyHolded(false)
      }
    }

    globalThis.addEventListener('keydown', onKeyDown)
    globalThis.addEventListener('keyup', onKeyUp)

    return () => {
      globalThis.removeEventListener('keydown', onKeyDown)
      globalThis.removeEventListener('keyup', onKeyUp)
    }
  }, [])

  // * EVENTS

  function onItemClick() {
    switch (true) {
      case !majKeyHolded && isDirectory(item):
        navigationService.navigateTo(item.path)
        selectNodes()
        return

      case !majKeyHolded && !isDirectory(item):
        selectNodes(item)
        return

      case majKeyHolded && isSelected:
        selectNodes(...selectedNodeList.filter(node => node.path !== item.path))
        return

      case majKeyHolded && !isSelected:
        selectNodes(...selectedNodeList, item)
        return
    }
  }

  function onItemDoubleClick() {
    if (!isDirectory(item) && !majKeyHolded) {
      setTargetFilePath(item.path)
      setViewFileModal(ModalState.Opened)
    }
  }

  function onKeyDown(e: React.KeyboardEvent<HTMLElement>) {
    switch (true) {
      case e.key === ' ' && majKeyHolded:
        e.preventDefault()
        if (isSelected) {
          selectNodes(...selectedNodeList.filter(node => node.path !== item.path))
        } else {
          selectNodes(...selectedNodeList, item)
        }
        return

      case e.key === ' ' && !isDirectory(item):
        e.preventDefault()
        selectNodes(item)
        return

      case e.key === 'Enter' && isDirectory(item):
        e.preventDefault()
        navigationService.navigateTo(item.path)
        return
    }
  }

  function onContextMenu(e: React.MouseEvent<HTMLElement>) {
    e.preventDefault()
    selectNodes(item)
  }

  function onMouseEnter() {
    if (!mouseHolded || !majKeyHolded) {
      return
    }

    if (!isSelected) {
      selectNodes(...selectedNodeList, item)
    }
  }

  if (isListView) {
    return (
      <div
        className={`table-row hover:cursor-pointer hover:bg-accent/30 ${isSelected ? 'bg-primary/20 ring-1 ring-primary' : ''}`}
        title={item.name}
        role="row"
        aria-selected={isSelected}
        tabIndex={tabIndex}
        onClick={onItemClick}
        onDoubleClick={onItemDoubleClick}
        onContextMenu={onContextMenu}
        onKeyDown={onKeyDown}
        onMouseEnter={onMouseEnter}
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
      className={`hover:cursor-pointer rounded-lg hover:bg-accent/30 ${isSelected ? 'bg-primary/20 ring-1 ring-primary' : ''}`}
      title={item.name}
      aria-pressed={isSelected}
      tabIndex={tabIndex}
      onClick={onItemClick}
      onDoubleClick={onItemDoubleClick}
      onContextMenu={onContextMenu}
      onKeyDown={onKeyDown}
      onMouseEnter={onMouseEnter}
    >
      {isDirectory(item) ? (
        <ContentDirectory item={item} asTile />
      ) : (
        <ContentFile item={item} asTile />
      )}
    </button>
  )
}
