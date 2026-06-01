import { useFileManagerStore } from "@ts/stores";
import { isDirectory } from "@ts/types";
import { X } from "lucide-react";
import ClipboardButtons from "./clipboard-buttons";
import CommonButtons from "./common-buttons";
import DirectoryButtons from "./directory-buttons";
import FileButtons from "./file-buttons";

interface SelectionBarProps {
}

export default function SelectionBar(_: Readonly<SelectionBarProps>) {
  const { selectedNode: selectedFile, selectNode } = useFileManagerStore()

  const isDir = selectedFile !== null && isDirectory(selectedFile)
  const visible = selectedFile !== null



  function onClickDeselect(_: React.MouseEvent<HTMLButtonElement>) {
    selectNode(null)
  }

  return (
    <div
      className={`flex items-center gap-2 px-4 h-9 overflow-hidden border-b transition-colors${visible ? ' bg-primary/10 border-primary/20' : ' bg-transparent border-base-300'}`}
      aria-hidden={!visible}
    >
      {selectedFile !== null && (
        <>
          <span className="text-sm font-medium text-base-content/70 truncate max-w-xs" title={selectedFile.name}>
            {selectedFile.name}
          </span>

          <div className="flex items-center gap-1 ml-2">
            {isDir ? (
              <DirectoryButtons selectedFile={selectedFile} />
            ) : (
              <FileButtons selectedFile={selectedFile} />
            )}
          </div>

          {/* CLIPBOARD ACTIONS */}
          <div className="flex items-center gap-1 ml-2">
            <ClipboardButtons selectedFile={selectedFile} />
          </div>

          {/* COMMON ACTIONS */}
          <div className="flex items-center gap-1 ml-2">
            <CommonButtons selectedFile={selectedFile} />
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
