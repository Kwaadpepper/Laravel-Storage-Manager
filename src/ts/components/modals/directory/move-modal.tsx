import DirectoryPicker from "@ts/components/shared/directory-picker";
import { useContainer } from "@ts/container";
import { ModalState, useFileManagerStore, useUiStore } from "@ts/stores";
import { Path, rootPath } from "@ts/types";
import { FolderPlus, X } from "lucide-react";
import { useEffect, useRef, useState } from "react";

export default function MoveModal() {
  const container = useContainer()
  const fileManagerService = container.resolve('fileManagerService')
  const toastService = container.resolve('toastService')
  const navigationService = container.resolve('navigationService')

  const eventQueueService = container.resolve('eventQueueService')

  const { moveModal, setMoveModal, setNewDirectoryModal, setTargetDirectoryPath } = useUiStore()
  const { selectedNodes, selectNodes } = useFileManagerStore()
  
  const dialogRef = useRef<HTMLDialogElement | null>(null)
  const closeButtonRef = useRef<HTMLButtonElement | null>(null)

  const [selectedDestination, setSelectedDestination] = useState<Path>(rootPath())
  const [isSubmitting, setIsSubmitting] = useState(false)

  const isOpen = moveModal === ModalState.Opened
  const selectedNodesList = Object.values(selectedNodes)

  useEffect(() => {
    if (isOpen) {
      dialogRef.current?.showModal()
    } else {
      dialogRef.current?.close()
      setSelectedDestination(rootPath())
      setIsSubmitting(false)
    }
  }, [isOpen])

  function onClose() {
    setMoveModal(ModalState.Closed)
  }

  async function onMove() {
    if (selectedNodesList.length === 0) return

    setIsSubmitting(true)

    const events = selectedNodesList.map(node => ({
      id: `move-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`,
      type: 'MOVE' as const,
      sourcePath: node.path,
      destinationPath: selectedDestination,
      execute: async () => {
        await fileManagerService.move(node.path, selectedDestination)
      }
    }))
    
    eventQueueService.pushBatch(events)
    selectNodes() // Clear selection
    
    setIsSubmitting(false)
    onClose()
  }

  return (
    <dialog className="modal" onClose={onClose} ref={dialogRef}>
      <div className="modal-box w-11/12 max-w-2xl flex flex-col h-[80vh]">
        <button
          className="btn btn-sm btn-circle btn-ghost absolute right-2 top-2"
          type="button"
          onClick={() => closeButtonRef.current?.click()}
        >
          <X size={16} />
        </button>
        <div className="flex items-center justify-between mb-4">
          <h3 className="font-bold text-lg">Move {selectedNodesList.length} item(s) to...</h3>
          <button 
            type="button"
            className="btn btn-sm btn-ghost gap-1 mr-6" 
            title="Create New Folder here"
            onClick={() => {
              setTargetDirectoryPath(selectedDestination)
              setNewDirectoryModal(ModalState.Opened)
            }}
          >
            <FolderPlus size={14} />
            <span className="hidden sm:inline">New Folder</span>
          </button>
        </div>

        <div className="flex-1 overflow-hidden relative">
           <DirectoryPicker 
             selectedPath={selectedDestination} 
             onSelect={setSelectedDestination} 
           />
        </div>

        <div className="mt-4 flex justify-between items-center gap-2">
           <div className="text-sm text-base-content/70 truncate flex-1">
             Destination: <span className="font-mono font-medium text-base-content">{selectedDestination}</span>
           </div>
           <button 
             className="btn btn-ghost" 
             onClick={() => closeButtonRef.current?.click()}
             disabled={isSubmitting}
           >
             Cancel
           </button>
           <button 
             className="btn btn-primary" 
             onClick={onMove}
             disabled={isSubmitting || selectedNodesList.length === 0}
           >
             {isSubmitting ? 'Moving...' : 'Move Here'}
           </button>
        </div>
      </div>

      <form method="dialog" className="modal-backdrop">
        <button ref={closeButtonRef}>Close</button>
      </form>
    </dialog>
  );
}
