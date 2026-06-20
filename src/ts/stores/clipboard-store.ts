import { create } from 'zustand';

interface ClipboardState {
  hasEntries: boolean
  isCutMode: boolean

  setHasEntries: (hasEntries: boolean) => void
  setIsCutMode: (isCutMode: boolean) => void
}

export const useClipboardStore = create<ClipboardState>((set) => ({
  hasEntries: false,
  isCutMode: false,

  setHasEntries: (hasEntries: boolean): void => {
    set({ hasEntries });
  },

  setIsCutMode: (isCutMode: boolean): void => {
    set({ isCutMode });
  },
}));
