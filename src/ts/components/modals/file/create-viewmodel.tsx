import { useContainer } from "@ts/container";
import { isDomainValidationError, isValidationError } from "@ts/errors";
import { ModalState, useFileManagerStore, useUiStore } from "@ts/stores";
import { Path } from "@ts/types";
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

export function useCreateFileViewModel() {
  const { currentPath } = useFileManagerStore()
  const { createFileModal, setCreateFileModal } = useUiStore()

  const container = useContainer()
  const navigationService = container.resolve('navigationService')
  const fileManagerService = container.resolve('fileManagerService')
  const toastService = container.resolve('toastService')

  const [baseName, setBaseName] = useState<string | null>(null)
  const [extension, setExtension] = useState<string | null>(null)

  const [formErrorMessage, setFormErrorMessage] = useState<string | null>(null)

  const isOpen = createFileModal === ModalState.Opened

  async function validateFileName(inputBaseName: string, inputExtension: string): Promise<{
    field: 'baseName' | 'extension',
    message: string
  }[] | null> {
    // Client-side format validation first
    const zodResult = await formSchema.parseAsync({ baseName: inputBaseName, extension: inputExtension })
      .then(() => null)
      .catch((error: unknown) => {
        if (error instanceof z.ZodError) {
          return error.issues.flatMap(issue => {
            const fieldName = issue.path[0]
            if (fieldName === baseNameInputName || fieldName === extensionInputName) {
              return [{ field: fieldName, message: issue.message }]
            }
            return []
          })
        }
        throw new Error('An unexpected error occurred.')
      })

    if (zodResult !== null) return zodResult

    // Backend existence check
    const trimmedExtension = inputExtension.trim()
    const newName = trimmedExtension ? `${inputBaseName}.${trimmedExtension}` : inputBaseName
    const candidatePath = `${currentPath.replace(/\/$/, '')}/${newName}` as Path

    const exists = await fileManagerService.fileExists(candidatePath)
    if (exists) {
      return [{ field: 'baseName', message: 'A file with this name already exists.' }]
    }

    return null
  }

  async function submit(inputBaseName: string, inputExtension: string, content: string = ''): Promise<boolean> {
    setFormErrorMessage(null)

    const trimmedExtension = inputExtension.trim()
    const newName = trimmedExtension ? `${inputBaseName}.${trimmedExtension}` : inputBaseName

    const eventQueueService = container.resolve('eventQueueService')
    
    eventQueueService.push({
      id: `create-file-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`,
      type: 'CREATE',
      sourcePath: currentPath,
      destinationPath: `${currentPath === '/' ? '' : currentPath}/${newName}`,
      execute: async () => {
        await fileManagerService.createFile(currentPath, newName, content)
      }
    })
    
    setCreateFileModal(ModalState.Closed)
    return true
  }

  function close() {
    setCreateFileModal(ModalState.Closed)
  }

  return {
    baseName,
    setBaseName,
    extension,
    setExtension,
    isOpen,
    currentPath,
    formErrorMessage,
    validateFileName,
    submit,
    close,
    baseNameInputName,
    baseNameMinLength,
    baseNameMaxLength,
    extensionMaxLength,
  }
}

