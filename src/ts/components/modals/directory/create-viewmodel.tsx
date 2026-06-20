import { useContainer } from "@ts/container";
import { isDomainValidationError, isValidationError } from "@ts/errors";
import { CreateDirectoryValidationField } from "@ts/services";
import { ModalState, useFileManagerStore, useUiStore } from "@ts/stores";
import { useState } from "react";
import { z } from "zod/v4";

export const directoryNameInputName = 'directoryName' as const
export const directoryNameMinLength = 1
export const directoryNameMaxLength = 255

export interface FormData {
  directoryName: string
}

export const formSchema = z.object({
  directoryName: z.string().min(directoryNameMinLength).max(directoryNameMaxLength)
})

export function useCreateDirectoryViewModel() {
  const { currentPath } = useFileManagerStore()
  const { newDirectoryModal, setNewDirectoryModal, targetDirectoryPath, setTargetDirectoryPath } = useUiStore()

  const activePath = targetDirectoryPath ?? currentPath

  const container = useContainer()
  const navigationService = container.resolve('navigationService')
  const fileManagerService = container.resolve('fileManagerService')
  const toastService = container.resolve('toastService')

  const [formErrorMessage, setFormErrorMessage] = useState<string | null>(null)
  const [directoryNameFieldError, setDirectoryNameFieldError] = useState<string | null>(null)

  const isOpen = newDirectoryModal === ModalState.Opened

  async function submit(directoryName: string): Promise<boolean> {
    setFormErrorMessage(null)
    setDirectoryNameFieldError(null)

    try {
      await fileManagerService.createDirectory(activePath, directoryName)
      setNewDirectoryModal(ModalState.Closed)
      setTargetDirectoryPath(null)
      navigationService.refreshCurrentPath()
      toastService.pushToast({ message: 'Directory created successfully.', type: 'success' })
      return true
    } catch (error: unknown) {
      if (isDomainValidationError(error)) {
        setFormErrorMessage(error.message)
        return false
      }

      if (isValidationError<CreateDirectoryValidationField>(error)) {
        const fieldErrors = error.getFieldErrors()
        const directoryError = fieldErrors[directoryNameInputName]

        if (directoryError) {
          setDirectoryNameFieldError(directoryError)
          return false
        }
      }

      throw error
    }
  }

  function close() {
    setNewDirectoryModal(ModalState.Closed)
    setTargetDirectoryPath(null)
  }

  return {
    isOpen,
    currentPath: activePath,
    formErrorMessage,
    directoryNameFieldError,
    submit,
    close,
  }
}
