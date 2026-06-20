import { Trash2, X } from "lucide-react";
import { useEffect, useRef } from "react";
import { useDeleteDirectoryViewModel } from "./delete-viewmodel";

interface DeleteDirectoryModalProps {
}

export default function DeleteDirectoryModal(_: Readonly<DeleteDirectoryModalProps>) {
  const vm = useDeleteDirectoryViewModel()

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
        <h3 className="font-bold text-lg">Delete Directory</h3>

        {vm.errorMessage && <p className="text-error text-center mt-2">{vm.errorMessage}</p>}

        <div className="py-4">
          <p>Are you sure you want to delete <span className="font-semibold">{vm.targetName}</span>?</p>
          <p className="text-sm text-base-content/60 mt-1 break-all">{vm.targetDirectoryPath}</p>
        </div>

        <div className="flex justify-center gap-2">
          <button className="btn" type="button" onClick={() => closeButtonRef.current?.click()}>Cancel</button>
          <button className="btn btn-error" type="button" onClick={() => vm.submit()}>
            <Trash2 size={16} />
            {vm.isForceConfirm ? 'Force Delete' : 'Delete'}
          </button>
        </div>
      </div>

      {/* Form to control the modal */}
      <form method="dialog" className="modal-backdrop">
        <button ref={closeButtonRef}>Close</button>
      </form>
    </dialog>
  );
}

