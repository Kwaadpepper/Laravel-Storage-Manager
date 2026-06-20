import { Trash2, X } from "lucide-react";
import { useEffect, useRef } from "react";
import { useDeleteViewModel } from "./delete-viewmodel";

export default function DeleteModal() {
  const vm = useDeleteViewModel()

  const dialogRef = useRef<HTMLDialogElement | null>(null)
  const closeButtonRef = useRef<HTMLButtonElement | null>(null)

  useEffect(() => {
    if (vm.isOpen) {
      dialogRef.current?.showModal()
    } else {
      dialogRef.current?.close()
    }
  }, [vm.isOpen])

  function onClose() {
    vm.close()
  }

  return (
    <dialog className="modal" onClose={onClose} ref={dialogRef}>
      <div className="modal-box">
        <button
          className="btn btn-sm btn-circle btn-ghost absolute right-2 top-2"
          type="button"
          onClick={() => closeButtonRef.current?.click()}
        >
          <X size={16} />
        </button>
        <h3 className="font-bold text-lg">Delete {vm.itemCount === 1 ? 'Item' : 'Items'}</h3>

        <div className="py-4">
          <p>
            Are you sure you want to delete {vm.itemCount === 1 ? (
              <span className="font-semibold">{vm.singleItemName}</span>
            ) : (
              <span className="font-semibold">{vm.itemCount} items</span>
            )}?
          </p>
        </div>

        <div className="flex justify-center gap-2">
          <button className="btn" type="button" onClick={() => closeButtonRef.current?.click()}>Cancel</button>
          <button className="btn btn-error" type="button" onClick={() => vm.submit()}>
            <Trash2 size={16} />
            Delete
          </button>
        </div>
      </div>

      <form method="dialog" className="modal-backdrop">
        <button ref={closeButtonRef}>Close</button>
      </form>
    </dialog>
  );
}
