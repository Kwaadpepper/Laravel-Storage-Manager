import { useContainer } from "@ts/container";
import { useClipboardStore, useFileManagerStore } from "@ts/stores";
import { TreeNode } from "@ts/types";
import { ClipboardCopy, ClipboardPaste, Scissors } from "lucide-react";

interface ClipboardButtonsProps {
  selectedNodes: TreeNode[]
}

export default function ClipboardButtons({ selectedNodes }: Readonly<ClipboardButtonsProps>) {
  const container = useContainer()
  const toastService = container.resolve('toastService')
  const clipboardService = container.resolve('clipboardService')
  const fileManagerService = container.resolve('fileManagerService')
  const navigationService = container.resolve('navigationService')
  const { currentPath } = useFileManagerStore()
  const { hasEntries } = useClipboardStore()
  const hasSelection = selectedNodes.length > 0

  function onClickCopy(_: React.MouseEvent<HTMLButtonElement>) {
    if (selectedNodes.length === 0) {
      return
    }
    clipboardService.setConsumingMode(false)
    clipboardService.addEntry(...selectedNodes.map(node => node.path))
    toastService.pushToast({ message: `${selectedNodes.length} item(s) copied to clipboard.`, type: 'success' })
  }

  function onClickCut(_: React.MouseEvent<HTMLButtonElement>) {
    if (selectedNodes.length === 0) {
      return
    }
    clipboardService.setConsumingMode(true)
    clipboardService.addEntry(...selectedNodes.map(node => node.path))
    toastService.pushToast({ message: `${selectedNodes.length} item(s) cut to clipboard.`, type: 'success' })
  }

  async function onClickPaste(_: React.MouseEvent<HTMLButtonElement>) {
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
  }

  return (
    <>
      {hasSelection && (
        <>
          <button
            type="button"
            className="btn btn-ghost btn-xs"
            title="Copy"
            onClick={onClickCopy}
          >
            <ClipboardCopy size={14} />
            <span className="hidden sm:inline">Copy</span>
          </button>

          <button
            type="button"
            className="btn btn-ghost btn-xs"
            title="Cut"
            onClick={onClickCut}
          >
            <Scissors size={14} />
            <span className="hidden sm:inline">Cut</span>
          </button>
        </>
      )}

      {hasEntries && (
        <button
          type="button"
          className="btn btn-ghost btn-xs"
          title="Paste"
          onClick={onClickPaste}
        >
          <ClipboardPaste size={14} />
          <span className="hidden sm:inline">Paste</span>
        </button>
      )}
    </>
  );
}
