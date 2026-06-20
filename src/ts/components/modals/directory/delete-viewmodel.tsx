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
  const [isForceConfirm, setIsForceConfirm] = useState(false)

  const isOpen = deleteDirectoryModal === ModalState.Opened
  const targetName = targetDirectoryPath?.split('/').pop() ?? ''

  async function submit(): Promise<boolean> {
    if (!targetDirectoryPath) return false
    setErrorMessage(null)

    try {
      await fileManagerService.deleteDirectory(targetDirectoryPath, isForceConfirm)
      setDeleteDirectoryModal(ModalState.Closed)
      navigationService.refreshCurrentPath()
      toastService.pushToast({ message: 'Directory deleted successfully.', type: 'success' })
      setIsForceConfirm(false)
      return true
    } catch (error: any) {
      if (isDomainValidationError(error)) {
        if (error.getDomainCode() === 1008) {
          setIsForceConfirm(true)
          setErrorMessage("This directory is not empty. Deleting it will permanently erase all its contents. Are you sure you want to proceed?")
          return false
        }
        setErrorMessage(error.message)
        return false
      }

      throw error
    }
  }

  function close() {
    setDeleteDirectoryModal(ModalState.Closed)
    setIsForceConfirm(false)
    setErrorMessage(null)
  }

  return {
    isOpen,
    targetDirectoryPath,
    targetName,
    errorMessage,
    submit,
    close,
    isForceConfirm,
  }
}
