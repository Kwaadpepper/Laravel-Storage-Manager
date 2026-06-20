import { useContainer } from "@ts/container";
import { isDomainValidationError, isValidationError } from "@ts/errors";
import { RenameFileValidationField } from "@ts/services";
import { ModalState, useUiStore } from "@ts/stores";
import { useState } from "react";
import { z } from "zod/v4";

export const baseNameInputName = 'baseName' as const
export const extensionInputName = 'extension' as const
export const baseNameMinLength = 1
export const baseNameMaxLength = 255
export const extensionMaxLength = 255

export interface FormData {
  baseName: string
  extension: string
}

export const formSchema = z.object({
  baseName: z.string().min(baseNameMinLength).max(baseNameMaxLength),
  extension: z.string().max(extensionMaxLength),
})

export function useRenameFileViewModel() {
  const { renameFileModal, setRenameFileModal, targetFilePath } = useUiStore()

  const container = useContainer()
  const navigationService = container.resolve('navigationService')
  const fileManagerService = container.resolve('fileManagerService')
  const toastService = container.resolve('toastService')

  const [formErrorMessage, setFormErrorMessage] = useState<string | null>(null)
  const [baseNameFieldError, setBaseNameFieldError] = useState<string | null>(null)

  const isOpen = renameFileModal === ModalState.Opened
  const targetName = targetFilePath?.split('/').pop() ?? ''
  const lastDot = targetName.lastIndexOf('.')
  const targetBaseName = lastDot > 0 ? targetName.slice(0, lastDot) : targetName
  const targetExtension = lastDot > 0 ? targetName.slice(lastDot + 1) : ''

  async function submit(baseName: string, extension: string): Promise<boolean> {
    if (!targetFilePath) return false
    setFormErrorMessage(null)
    setBaseNameFieldError(null)

    const trimmedExtension = extension.trim()
    const newName = trimmedExtension ? `${baseName}.${trimmedExtension}` : baseName

    const eventQueueService = container.resolve('eventQueueService')
    
    eventQueueService.push({
      id: `rename-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`,
      type: 'RENAME',
      sourcePath: targetFilePath,
      destinationPath: targetFilePath.substring(0, targetFilePath.lastIndexOf('/')) + '/' + newName,
      execute: async () => {
        await fileManagerService.renameFile(targetFilePath, newName)
      }
    })
    
    setRenameFileModal(ModalState.Closed)
    return true
  }

  function close() {
    setRenameFileModal(ModalState.Closed)
  }

  return {
    isOpen,
    targetFilePath,
    targetBaseName,
    targetExtension,
    formErrorMessage,
    baseNameFieldError,
    submit,
    close,
  }
}
