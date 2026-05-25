import { Path } from '@ts/types';
import { create } from 'zustand';

export enum ModalState {
  Closed = 'closed',
  Opened = 'opened',
}

type UiState = {
  aboutModal: ModalState
  setAboutModal: (state: ModalState) => void
  newDirectoryModal: ModalState
  setNewDirectoryModal: (state: ModalState) => void
  renameDirectoryModal: ModalState
  setRenameDirectoryModal: (state: ModalState) => void
  deleteDirectoryModal: ModalState
  setDeleteDirectoryModal: (state: ModalState) => void
  uploadFileModal: ModalState
  setUploadFileModal: (state: ModalState) => void
  targetDirectoryPath: Path | null
  setTargetDirectoryPath: (path: Path | null) => void
}

export const useUiStore = create<UiState>((set) => ({
  aboutModal: ModalState.Closed,
  setAboutModal: (state) => set({ aboutModal: state }),
  newDirectoryModal: ModalState.Closed,
  setNewDirectoryModal: (state) => set({ newDirectoryModal: state }),
  renameDirectoryModal: ModalState.Closed,
  setRenameDirectoryModal: (state) => set({ renameDirectoryModal: state }),
  deleteDirectoryModal: ModalState.Closed,
  setDeleteDirectoryModal: (state) => set({ deleteDirectoryModal: state }),
  uploadFileModal: ModalState.Closed,
  setUploadFileModal: (state) => set({ uploadFileModal: state }),
  targetDirectoryPath: null,
  setTargetDirectoryPath: (path) => set({ targetDirectoryPath: path }),
}))
