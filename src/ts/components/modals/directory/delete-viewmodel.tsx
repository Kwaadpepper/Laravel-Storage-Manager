import { useContainer } from "@ts/container";
import { isDomainValidationError } from "@ts/errors";
import { ModalState, useUiStore } from "@ts/stores";
import { useState } from "react";

export function useDeleteDirectoryViewModel() {
  const { deleteDirectoryModal, setDeleteDirectoryModal, targetDirectoryPath } = useUiStore()

  const container = useContainer()
  const navigationService = container.resolve('navigationService')
  const fileManagerService = container.resolve('fileManagerService')
  const toastService = container.resolve('toastService')

  const [errorMessage, setErrorMessage] = useState<string | null>(null)

  const isOpen = deleteDirectoryModal === ModalState.Opened
  const targetName = targetDirectoryPath?.split('/').pop() ?? ''

  async function submit(): Promise<boolean> {
    if (!targetDirectoryPath) return false
    setErrorMessage(null)

    try {
      await fileManagerService.deleteDirectory(targetDirectoryPath)
      setDeleteDirectoryModal(ModalState.Closed)
      navigationService.refreshCurrentPath()
      toastService.pushToast({ message: 'Directory deleted successfully.', type: 'success' })
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
    setDeleteDirectoryModal(ModalState.Closed)
  }

  return {
    isOpen,
    targetDirectoryPath,
    targetName,
    errorMessage,
    submit,
    close,
  }
}
