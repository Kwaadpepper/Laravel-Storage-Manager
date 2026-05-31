import { Disk } from '@ts/types';
import { create } from 'zustand';

interface DiskState {
  currentDisk: Disk | null
  setCurrentDisk: (disk: Disk) => void
}

export const useDiskStore = create<DiskState>((set) => ({
  currentDisk: null,
  setCurrentDisk: (disk) => set({ currentDisk: disk }),
}))
