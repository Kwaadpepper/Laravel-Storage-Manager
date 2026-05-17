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
  uploadFileModal: ModalState
  setUploadFileModal: (state: ModalState) => void
}

export const useUiStore = create<UiState>((set) => ({
  aboutModal: ModalState.Closed,
  setAboutModal: (state) => set({ aboutModal: state }),
  newDirectoryModal: ModalState.Closed,
  setNewDirectoryModal: (state) => set({ newDirectoryModal: state }),
  uploadFileModal: ModalState.Closed,
  setUploadFileModal: (state) => set({ uploadFileModal: state }),
}))
