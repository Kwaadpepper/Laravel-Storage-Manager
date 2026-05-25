import { useContainer } from "@ts/container";
import { isDomainValidationError, isValidationError } from "@ts/errors";
import { RenameDirectoryValidationField } from "@ts/services";
import { ModalState, useUiStore } from "@ts/stores";
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

export function useRenameDirectoryViewModel() {
  const { renameDirectoryModal, setRenameDirectoryModal, targetDirectoryPath } = useUiStore()

  const container = useContainer()
  const navigationService = container.resolve('navigationService')
  const fileManagerService = container.resolve('fileManagerService')
  const toastService = container.resolve('toastService')

  const [formErrorMessage, setFormErrorMessage] = useState<string | null>(null)
  const [directoryNameFieldError, setDirectoryNameFieldError] = useState<string | null>(null)

  const isOpen = renameDirectoryModal === ModalState.Opened
  const targetName = targetDirectoryPath?.split('/').pop() ?? ''

  async function submit(newName: string): Promise<boolean> {
    if (!targetDirectoryPath) return false
    setFormErrorMessage(null)
    setDirectoryNameFieldError(null)

    try {
      await fileManagerService.renameDirectory(targetDirectoryPath, newName)
      setRenameDirectoryModal(ModalState.Closed)
      navigationService.refreshCurrentPath()
      toastService.pushToast({ message: 'Directory renamed successfully.', type: 'success' })
      return true
    } catch (error: unknown) {
      if (isDomainValidationError(error)) {
        setFormErrorMessage(error.message)
        return false
      }

      if (isValidationError<RenameDirectoryValidationField>(error)) {
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
    setRenameDirectoryModal(ModalState.Closed)
  }

  return {
    isOpen,
    targetDirectoryPath,
    targetName,
    formErrorMessage,
    directoryNameFieldError,
    submit,
    close,
  }
}
