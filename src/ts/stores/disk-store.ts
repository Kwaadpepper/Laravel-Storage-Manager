import { Disk } from '@ts/types';
import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useDiskStore = defineStore('disk', () => {
  const currentDisk = ref<Disk | null>(null);

  function setCurrentDisk(disk: Disk): void {
    currentDisk.value = disk;
  }

  return {
    currentDisk,
    setCurrentDisk,
  };
});

export type DiskStore = ReturnType<typeof useDiskStore>;
