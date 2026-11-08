import { getApiClient } from '@ts/clients';
import { DiskConfig, diskStatsSchema } from '@ts/schemas';
import { useConfigStore } from '@ts/stores/config-store';
import { Disk, DiskStats } from '@ts/types';
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

export const useDiskStore = defineStore('disk', () => {
  const currentDisk = ref<Disk | null>(null);
  const stats = ref<DiskStats | null>(null);
  const isLoadingStats = ref<boolean>(false);
  const statsError = ref<string | null>(null);

  const currentDiskConfig = computed<DiskConfig | undefined>(() => {
    const configStore = useConfigStore();
    return currentDisk.value ? configStore.getDiskConfig(currentDisk.value) : undefined;
  });

  function setCurrentDisk(disk: Disk): void {
    currentDisk.value = disk;
  }

  function setStats(newStats: DiskStats | null): void {
    stats.value = newStats;
  }

  function getBaseUrl(): string {
    return typeof window !== 'undefined' ? (window.location?.origin || 'http://localhost') : 'http://localhost';
  }

  async function selectDisk(disk: Disk): Promise<void> {
    const configStore = useConfigStore();
    const apiClient = getApiClient();
    const url = configStore.routes.disksSelect;
    const path = new URL(url, getBaseUrl()).pathname;
    await apiClient.post(path, { disk });
    setCurrentDisk(disk);
    if (configStore.supportsDiskStats) {
      void fetchDiskStats(disk);
    }
  }

  async function fetchDiskStats(disk?: Disk, fresh: boolean = false): Promise<DiskStats | null> {
    const configStore = useConfigStore();
    const targetDisk = disk ?? currentDisk.value;
    if (!targetDisk || !configStore.supportsDiskStats) {
      return null;
    }

    const routeUrl = configStore.routes.disksStats;
    if (!routeUrl) {
      return null;
    }

    isLoadingStats.value = true;
    statsError.value = null;

    try {
      const apiClient = getApiClient();
      const url = new URL(routeUrl, getBaseUrl());
      url.searchParams.set('disk', targetDisk);
      if (fresh) {
        url.searchParams.set('fresh', '1');
      }
      const newStats = await apiClient.get(`${url.pathname}${url.search}`, diskStatsSchema);
      if (currentDisk.value === targetDisk) {
        setStats(newStats);
      }
      return newStats;
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Failed to fetch disk stats';
      if (currentDisk.value === targetDisk) {
        statsError.value = message;
      }
      return null;
    } finally {
      if (currentDisk.value === targetDisk) {
        isLoadingStats.value = false;
      }
    }
  }

  return {
    currentDisk,
    currentDiskConfig,
    stats,
    isLoadingStats,
    statsError,
    setCurrentDisk,
    setStats,
    selectDisk,
    fetchDiskStats,
  };
});

export type DiskStore = ReturnType<typeof useDiskStore>;
