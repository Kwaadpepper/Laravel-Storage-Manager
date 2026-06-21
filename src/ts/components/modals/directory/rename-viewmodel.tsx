import { useContainer } from "@ts/container";
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
  const fileManagerService = container.resolve('fileManagerService')

  const [formErrorMessage, setFormErrorMessage] = useState<string | null>(null)
  const [directoryNameFieldError, setDirectoryNameFieldError] = useState<string | null>(null)

  const isOpen = renameDirectoryModal === ModalState.Opened
  const targetName = targetDirectoryPath?.split('/').pop() ?? ''

  async function submit(newName: string): Promise<boolean> {
    if (!targetDirectoryPath) return false
    setFormErrorMessage(null)
    setDirectoryNameFieldError(null)

    const eventQueueService = container.resolve('eventQueueService')
    
    eventQueueService.push({
      id: `rename-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`,
      type: 'RENAME',
      sourcePath: targetDirectoryPath,
      destinationPath: targetDirectoryPath.substring(0, targetDirectoryPath.lastIndexOf('/')) + '/' + newName,
      execute: async () => {
        await fileManagerService.renameDirectory(targetDirectoryPath, newName)
      }
    })
    
    setRenameDirectoryModal(ModalState.Closed)
    return true
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
