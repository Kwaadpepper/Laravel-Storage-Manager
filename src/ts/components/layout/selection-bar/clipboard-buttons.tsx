import { useContainer } from "@ts/container";
import { TreeNode } from "@ts/types";
import { ClipboardCopy, ClipboardPaste, Scissors } from "lucide-react";

interface ClipboardButtonsProps {
  selectedFile: TreeNode | null
}

export default function ClipboardButtons({ selectedFile }: Readonly<ClipboardButtonsProps>) {
  const container = useContainer()
  const toastService = container.resolve('toastService')
  const clipboardService = container.resolve('clipboardService')

    function onClickCopy(_: React.MouseEvent<HTMLButtonElement>) {
    if (selectedFile === null) {
      return
    }
    clipboardService.setConsumingMode(false)
    clipboardService.addEntry(selectedFile.path)
    toastService.pushToast({ message: 'File path copied to clipboard.', type: 'success' })
  }

  function onClickCut(_: React.MouseEvent<HTMLButtonElement>) {
    if (selectedFile === null) {
      return
    }
    clipboardService.setConsumingMode(true)
    clipboardService.addEntry(selectedFile.path)
    toastService.pushToast({ message: 'File path cut to clipboard.', type: 'success' })
  }

  function onClickPaste(_: React.MouseEvent<HTMLButtonElement>) {
    if (selectedFile === null) {
      return
    }
    const entry = clipboardService.getLastEntry()
    if (entry === null) {
      toastService.pushToast({ message: 'Clipboard is empty.', type: 'info' })
      return
    }
    // TODO: Implement paste functionality
    toastService.pushToast({ message: 'Paste functionality is not implemented yet.', type: 'info' })
  }

  return (
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

      <button
        type="button"
        className="btn btn-ghost btn-xs"
        title="Paste"
        onClick={onClickPaste}
      >
        <ClipboardPaste size={14} />
        <span className="hidden sm:inline">Paste</span>
      </button>
    </>
  );
}
