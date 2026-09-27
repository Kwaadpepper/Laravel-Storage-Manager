import { Disk, DiskStats } from '@ts/types';
import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useDiskStore = defineStore('disk', () => {
  const currentDisk = ref<Disk | null>(null);
  const stats = ref<DiskStats | null>(null);
  const isLoadingStats = ref<boolean>(false);
  const statsError = ref<string | null>(null);

  function setCurrentDisk(disk: Disk): void {
    currentDisk.value = disk;
  }

  function setStats(newStats: DiskStats | null): void {
    stats.value = newStats;
  }

  return {
    currentDisk,
    stats,
    isLoadingStats,
    statsError,
    setCurrentDisk,
    setStats,
  };
});

export type DiskStore = ReturnType<typeof useDiskStore>;
