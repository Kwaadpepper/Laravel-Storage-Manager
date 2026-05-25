import { zodResolver } from "@hookform/resolvers/zod";
import { Pencil, X } from "lucide-react";
import { useEffect, useRef } from "react";
import { useForm } from "react-hook-form";
import FieldError from "../../shared/field-error";
import {
  fileNameInputName,
  fileNameMaxLength,
  fileNameMinLength,
  FormData,
  formSchema,
  useRenameFileViewModel,
} from "./rename-viewmodel";

interface RenameFileModalProps {
}

export default function RenameFileModal(_: Readonly<RenameFileModalProps>) {
  const vm = useRenameFileViewModel()

  const dialogRef = useRef<HTMLDialogElement | null>(null)
  const fileNameInputRef = useRef<HTMLInputElement | null>(null)
  const closeButtonRef = useRef<HTMLButtonElement | null>(null)

  const {
    register,
    handleSubmit,
    formState: { errors },
    setError,
    reset,
  } = useForm<FormData>({ resolver: zodResolver(formSchema) })

  const { ref: registerRef, ...registerRest } = register(fileNameInputName)

  useEffect(() => {
    if (vm.isOpen) {
      dialogRef.current?.showModal()
      fileNameInputRef.current?.focus()
    } else {
      dialogRef.current?.close()
    }
  }, [vm.isOpen])

  useEffect(() => {
    if (vm.fileNameFieldError) {
      setError(fileNameInputName, { message: vm.fileNameFieldError })
    }
  }, [vm.fileNameFieldError])

  const onFormSubmit = async (data: FormData) => {
    const success = await vm.submit(data.fileName)
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
        <h3 className="font-bold text-lg">Rename File</h3>

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
              minLength={fileNameMinLength}
              maxLength={fileNameMaxLength}
              required
              aria-invalid={errors.fileName ? 'true' : 'false'}
              ref={(el) => {
                registerRef(el)
                fileNameInputRef.current = el
              }}
              {...registerRest}
            />
          </label>
          <FieldError fieldError={errors.fileName} />

          <label className="input mx-auto">
            <span>Rename</span>
            <input className="grow" type="text" value={vm.targetFilePath ?? ''} readOnly disabled />
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
