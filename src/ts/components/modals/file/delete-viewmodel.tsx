import { useContainer } from "@ts/container";
import { isDomainValidationError } from "@ts/errors";
import { ModalState, useUiStore } from "@ts/stores";
import { useState } from "react";

export function useDeleteFileViewModel() {
  const { deleteFileModal, setDeleteFileModal, targetFilePath } = useUiStore()

  const container = useContainer()
  const navigationService = container.resolve('navigationService')
  const fileManagerService = container.resolve('fileManagerService')
  const toastService = container.resolve('toastService')

  const [errorMessage, setErrorMessage] = useState<string | null>(null)

  const isOpen = deleteFileModal === ModalState.Opened
  const targetName = targetFilePath?.split('/').pop() ?? ''

  async function submit(): Promise<boolean> {
    if (!targetFilePath) return false
    setErrorMessage(null)

    try {
      await fileManagerService.deleteFile(targetFilePath)
      setDeleteFileModal(ModalState.Closed)
      navigationService.refreshCurrentPath()
      toastService.pushToast({ message: 'File deleted successfully.', type: 'success' })
      return true
    } catch (error: unknown) {
      if (isDomainValidationError(error)) {
        setErrorMessage(error.message)
        return false
      }

      throw error
    }
  }

  function close() {
    setDeleteFileModal(ModalState.Closed)
  }

  return {
    isOpen,
    targetFilePath,
    targetName,
    errorMessage,
    submit,
    close,
  }
}
