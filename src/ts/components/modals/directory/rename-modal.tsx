import { zodResolver } from "@hookform/resolvers/zod";
import { Pencil, X } from "lucide-react";
import { useEffect, useRef } from "react";
import { useForm } from "react-hook-form";
import FieldError from "../../shared/field-error";
import {
  directoryNameInputName,
  directoryNameMaxLength,
  directoryNameMinLength,
  FormData,
  formSchema,
  useRenameDirectoryViewModel,
} from "./rename-viewmodel";

interface RenameDirectoryModalProps {
}

export default function RenameDirectoryModal(_: Readonly<RenameDirectoryModalProps>) {
  const vm = useRenameDirectoryViewModel()

  const dialogRef = useRef<HTMLDialogElement | null>(null)
  const directoryNameInputRef = useRef<HTMLInputElement | null>(null)
  const closeButtonRef = useRef<HTMLButtonElement | null>(null)

  const {
    register,
    handleSubmit,
    formState: { errors },
    setError,
    reset,
  } = useForm<FormData>({ resolver: zodResolver(formSchema) })

  const { ref: registerRef, ...registerRest } = register(directoryNameInputName)

  useEffect(() => {
    if (vm.isOpen) {
      dialogRef.current?.showModal()
      directoryNameInputRef.current?.focus()
    } else {
      dialogRef.current?.close()
    }
  }, [vm.isOpen])

  useEffect(() => {
    if (vm.directoryNameFieldError) {
      setError(directoryNameInputName, { message: vm.directoryNameFieldError })
    }
  }, [vm.directoryNameFieldError])

  const onFormSubmit = async (data: FormData) => {
    const success = await vm.submit(data.directoryName)
    if (success) reset()
  }

  function onClose() {
    vm.close()
    reset()
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
        <h3 className="font-bold text-lg">Rename Directory</h3>

        {vm.formErrorMessage && <p className="text-error text-center mt-2">{vm.formErrorMessage}</p>}

        <form className="fieldset flex flex-col justify-center gap-4 py-4" onSubmit={handleSubmit(onFormSubmit)}>

          <label className="input validator mx-auto">
            <Pencil className="h-[1em] opacity-50" />
            <input
              className="grow"
              type="text"
              placeholder={vm.targetName}
              autoFocus
              autoComplete="off"
              minLength={directoryNameMinLength}
              maxLength={directoryNameMaxLength}
              required
              aria-invalid={errors.directoryName ? 'true' : 'false'}
              ref={(el) => {
                registerRef(el)
                directoryNameInputRef.current = el
              }}
              {...registerRest}
            />
          </label>
          <FieldError fieldError={errors.directoryName} />

          <label className="input mx-auto">
            <span>Rename</span>
            <input className="grow" type="text" value={vm.targetDirectoryPath ?? ''} readOnly disabled />
          </label>

          <span className="flex justify-center">
            <button className="btn btn-primary" type="submit">Rename</button>
          </span>
        </form>
      </div>

      {/* Form to control the modal */}
      <form method="dialog" className="modal-backdrop">
        <button ref={closeButtonRef}>Close</button>
      </form>
    </dialog>
  );
}

