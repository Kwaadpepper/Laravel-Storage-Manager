import { zodResolver } from "@hookform/resolvers/zod";
import { useContainer } from "@ts/container";
import { isDomainValidationError, isValidationError } from "@ts/errors";
import { CreateDirectoryValidationField } from "@ts/services";
import { ModalState, useFileManagerStore, useUiStore } from "@ts/stores";
import { FolderPlus, X } from "lucide-react";
import { useEffect, useRef, useState } from "react";
import { useForm } from "react-hook-form";
import { z } from "zod/v4";
import FieldError from "../shared/field-error";

interface DirectoryModalProps {
}

interface FormData {
  directoryName: string
}

const directoryNameInputName = 'directoryName'
const directoryNameMinLength = 1
const directoryNameMaxLength = 255

const formSchema = z.object({
  directoryName: z.string().min(directoryNameMinLength).max(directoryNameMaxLength)
})

export default function DirectoryModal(_: Readonly<DirectoryModalProps>) {
  const { currentPath } = useFileManagerStore()
  const { newDirectoryModal, setNewDirectoryModal } = useUiStore()

  const container = useContainer()
  const navigationService = container.cradle.navigationService
  const fileManagerService = container.cradle.fileManagerService
  const toastService = container.cradle.toastService

  const [formErrorMessage, setFormErrorMessage] = useState<string | null>(null)

  const dialogRef = useRef<HTMLDialogElement | null>(null)
  const directoryNameInputRef = useRef<HTMLInputElement | null>(null)
  const closeButtonRef = useRef<HTMLButtonElement | null>(null)
  const closeInnerButtonRef = useRef<HTMLButtonElement | null>(null)

  const {
    register,
    handleSubmit,
    formState: { errors },
    setError,
    reset
  } = useForm<FormData>({
    resolver: zodResolver(formSchema)
  });

  const { ref: registerRef, ...registerRest } = register(directoryNameInputName)

  useEffect(() => {
    if (newDirectoryModal === ModalState.Opened) {
      dialogRef?.current?.showModal()
      directoryNameInputRef.current?.focus()
    } else {
      dialogRef?.current?.close()
    }

    closeInnerButtonRef.current?.addEventListener('click', onInnerCloseButtonClick)

    return () => {
      closeInnerButtonRef.current?.removeEventListener('click', onInnerCloseButtonClick)
    }
  }, [newDirectoryModal])

  const onFormSubmit = async (data: FormData) => {
    setFormErrorMessage(null)

    try {
      await fileManagerService.createDirectory(currentPath, data.directoryName)
      setNewDirectoryModal(ModalState.Closed)
      navigationService.refreshCurrentPath()
      toastService.pushToast({ message: 'Directory created successfully.', type: 'success' })
      reset()
    } catch (error: unknown) {
      if (isDomainValidationError(error)) {
        setFormErrorMessage(error.message)
        return
      }

      if (isValidationError<CreateDirectoryValidationField>(error)) {
        const fieldErrors = error.getFieldErrors()
        const directoryError = fieldErrors[directoryNameInputName]

        if (directoryError) {
          setError(directoryNameInputName, { message: directoryError })
          return
        }
      }

      throw error
    }
  }

  function onInnerCloseButtonClick() {
    closeButtonRef.current?.click()
    reset()
    setFormErrorMessage(null)
  }

  return (
    <dialog className="modal" onClose={() => setNewDirectoryModal(ModalState.Closed)} ref={dialogRef}>
      <div className="modal-box">
        <button className="btn btn-sm btn-circle btn-ghost absolute right-2 top-2" ref={closeInnerButtonRef}>
          <X size={16} />
        </button>
        <h3 className="font-bold text-lg">Create New Directory</h3>

        {formErrorMessage && <p className="text-error text-center mt-2">{formErrorMessage}</p>}

        <form className="fieldset flex flex-col justify-center gap-4 py-4" onSubmit={handleSubmit(onFormSubmit)}>

          <label className="input validator mx-auto">
            <FolderPlus className="h-[1em] opacity-50" />
            <input
              className="grow"
              type="text"
              placeholder="My New Directory"
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
            <span>Into</span>
            <input className="grow" type="text" placeholder={currentPath} readOnly disabled />
          </label>

          <span className="flex justify-center">
            <button className="btn btn-primary" type="submit">Create</button>
          </span>
        </form>
      </div>

      {/* Form to control the modal */}
      <form method="dialog" className="modal-backdrop" onSubmit={onInnerCloseButtonClick}>
        <button ref={closeButtonRef}>Close</button>
      </form>
    </dialog>
  );
}
