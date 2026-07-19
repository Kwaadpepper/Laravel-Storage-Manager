import { zodResolver } from "@hookform/resolvers/zod";
import { Pencil, X } from "lucide-react";
import { useEffect, useRef } from "react";
import { useForm } from "react-hook-form";
import FieldError from "../../shared/field-error";
import {
  baseNameInputName,
  baseNameMaxLength,
  baseNameMinLength,
  extensionInputName,
  extensionMaxLength,
  FormData,
  formSchema,
  useRenameFileViewModel,
} from "./rename-viewmodel";

type RenameFileModalProps = Record<string, never>;

export default function RenameFileModal(_: Readonly<RenameFileModalProps>) {
  const vm = useRenameFileViewModel()

  const dialogRef = useRef<HTMLDialogElement | null>(null)
  const baseNameInputRef = useRef<HTMLInputElement | null>(null)
  const closeButtonRef = useRef<HTMLButtonElement | null>(null)

  const {
    register,
    handleSubmit,
    formState: { errors },
    setError,
    reset,
  } = useForm<FormData>({ resolver: zodResolver(formSchema) })

  const { ref: baseNameRegisterRef, ...baseNameRegisterRest } = register(baseNameInputName)
  const { ref: extensionRegisterRef, ...extensionRegisterRest } = register(extensionInputName)

  useEffect(() => {
    if (vm.isOpen) {
      reset({ baseName: vm.targetBaseName, extension: vm.targetExtension })
      dialogRef.current?.showModal()
      baseNameInputRef.current?.focus()
    } else {
      dialogRef.current?.close()
    }
  }, [vm.isOpen, reset, vm.targetBaseName, vm.targetExtension])

  useEffect(() => {
    if (vm.baseNameFieldError) {
      setError(baseNameInputName, { message: vm.baseNameFieldError })
    }
  }, [vm.baseNameFieldError, setError])

  const onFormSubmit = async (data: FormData) => {
    const success = await vm.submit(data.baseName, data.extension)
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

          <div className="flex items-start gap-1 mx-auto">
            <div className="flex flex-col gap-1">
              <label className="input validator">
                <Pencil className="h-[1em] opacity-50" />
                <input
                  className="grow"
                  type="text"
                  placeholder={vm.targetBaseName}
                  autoFocus
                  autoComplete="off"
                  minLength={baseNameMinLength}
                  maxLength={baseNameMaxLength}
                  required
                  aria-invalid={errors.baseName ? 'true' : 'false'}
                  ref={(el) => {
                    baseNameRegisterRef(el)
                    baseNameInputRef.current = el
                  }}
                  {...baseNameRegisterRest}
                />
              </label>
              <FieldError fieldError={errors.baseName} />
            </div>

            <span className="self-center px-1 text-base-content/60 select-none">.</span>

            <div className="flex flex-col gap-1">
              <label className="input">
                <input
                  className="grow w-24"
                  type="text"
                  placeholder={vm.targetExtension || 'ext'}
                  autoComplete="off"
                  maxLength={extensionMaxLength}
                  aria-label="Extension"
                  aria-invalid={errors.extension ? 'true' : 'false'}
                  ref={extensionRegisterRef}
                  {...extensionRegisterRest}
                />
              </label>
              <FieldError fieldError={errors.extension} />
            </div>
          </div>

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
