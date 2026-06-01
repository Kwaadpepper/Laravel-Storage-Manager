import { useFileManagerStore } from "@ts/stores";
import { X } from "lucide-react";
import ClipboardButtons from "./clipboard-buttons";
import CommonButtons from "./common-buttons";
import DirectoryButtons from "./directory-buttons";
import FileButtons from "./file-buttons";

interface SelectionBarProps {
}

export default function SelectionBar(_: Readonly<SelectionBarProps>) {
  const { selectedNodes, selectNodes: selectNode } = useFileManagerStore()
  const visible = Object.keys(selectedNodes).length > 0
  const selectedLabel = Object.values(selectedNodes)
    .reduce((label, node) => label.length ? 'Multiple selections' : node.name, '')

  function onClickDeselect(_: React.MouseEvent<HTMLButtonElement>) {
    selectNode()
  }

  return (
    <div
      className={`flex items-center gap-2 px-4 h-9 overflow-hidden border-b transition-colors${visible ? ' bg-primary/10 border-primary/20' : ' bg-transparent border-base-300'}`}
      aria-hidden={!visible}
    >
      {visible && (
        <>
          <span className="text-sm font-medium text-base-content/70 truncate max-w-xs" title={selectedLabel}>
            {selectedLabel}
          </span>

          <div className="flex items-center gap-1 ml-2">
            <DirectoryButtons selectedNodes={Object.values(selectedNodes)} />
            <FileButtons selectedNodes={Object.values(selectedNodes)} />
          </div>

          {/* CLIPBOARD ACTIONS */}
          <div className="flex items-center gap-1 ml-2">
            <ClipboardButtons selectedNodes={Object.values(selectedNodes)} />
          </div>

          {/* COMMON ACTIONS */}
          <div className="flex items-center gap-1 ml-2">
            <CommonButtons selectedNodes={Object.values(selectedNodes)} />
          </div>

          <button
            type="button"
            className="btn btn-ghost btn-xs ml-auto"
            title="Deselect"
            onClick={onClickDeselect}
          >
            <X size={14} />
          </button>
        </>
      )}
    </div>
  )
}
