import { useContainer } from "@ts/container";
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
  const fileManagerService = container.resolve('fileManagerService')

  const [formErrorMessage, setFormErrorMessage] = useState<string | null>(null)
  const [directoryNameFieldError, setDirectoryNameFieldError] = useState<string | null>(null)

  const isOpen = newDirectoryModal === ModalState.Opened

  async function submit(directoryName: string): Promise<boolean> {
    setFormErrorMessage(null)
    setDirectoryNameFieldError(null)

    const eventQueueService = container.resolve('eventQueueService')
    
    eventQueueService.push({
      id: `create-dir-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`,
      type: 'CREATE',
      sourcePath: activePath,
      destinationPath: `${activePath === '/' ? '' : activePath}/${directoryName}`,
      execute: async () => {
        await fileManagerService.createDirectory(activePath, directoryName)
      }
    })
    
    setNewDirectoryModal(ModalState.Closed)
    setTargetDirectoryPath(null)
    return true
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
