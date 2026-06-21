import { useFileManagerStore } from "@ts/stores";
import { X } from "lucide-react";
import ClipboardButtons from "./clipboard-buttons";
import CommonButtons from "./common-buttons";
import DirectoryButtons from "./directory-buttons";
import FileButtons from "./file-buttons";

interface ContextBarProps {
}

export default function ContextBar(_: Readonly<ContextBarProps>) {
  const { currentBaseName, selectedNodes, selectNodes: selectNode } = useFileManagerStore()
  const selectedNodesLabel = Object.values(selectedNodes)
    .reduce((label, node) => label.length ? 'Multiple selections' : node.name, '')

  function onClickDeselect(_: React.MouseEvent<HTMLButtonElement>) {
    selectNode()
  }

  return (
    <div
      className={`flex items-center gap-2 px-4 h-9 border-b transition-colors bg-primary/10 border-primary/20`}
    >

      {selectedNodesLabel.length ? (
        <span className="text-sm font-medium text-base-content/70 truncate max-w-xs" title={selectedNodesLabel}>
          {selectedNodesLabel}
        </span>
      ) : <span className="text-md font-bold italic text-base-content/70 truncate max-w-xs" title={currentBaseName}>
        {currentBaseName}
      </span>}

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

      {Object.values(selectedNodes).length > 0 && (
        <button
          type="button"
          className="btn btn-ghost btn-xs ml-auto"
          title="Deselect"
          onClick={onClickDeselect}
        >
          <X size={14} />
        </button>
      )}

    </div>
  )
}
