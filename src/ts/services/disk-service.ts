import { diskStatsSchema } from '@ts/schemas';
import { ConfigStore, DiskStore } from '@ts/stores';
import { Disk, DiskStats } from '@ts/types';
import { ApiService } from './api-service';

export class DiskService {
  constructor(
    private readonly apiService: ApiService,
    private readonly configStore: ConfigStore,
    private readonly diskStore: DiskStore,
  ) {}

  async selectDisk(disk: Disk): Promise<void> {
    const url = this.configStore.routes.disksSelect;
    const path = new URL(url).pathname;
    await this.apiService.post(path, { disk });
    this.diskStore.setCurrentDisk(disk);
    if (this.configStore.supportsDiskStats) {
      void this.fetchDiskStats(disk);
    }
  }

  async fetchDiskStats(disk?: Disk, fresh: boolean = false): Promise<DiskStats | null> {
    const targetDisk = disk ?? this.diskStore.currentDisk;
    if (!targetDisk || !this.configStore.supportsDiskStats) {
      return null;
    }

    const routeUrl = this.configStore.routes.disksStats;
    if (!routeUrl) {
      return null;
    }

    this.diskStore.isLoadingStats = true;
    this.diskStore.statsError = null;

    try {
      const url = new URL(routeUrl);
      url.searchParams.set('disk', targetDisk);
      if (fresh) {
        url.searchParams.set('fresh', '1');
      }
      const stats = await this.apiService.get(`${url.pathname}${url.search}`, diskStatsSchema);
      if (this.diskStore.currentDisk === targetDisk) {
        this.diskStore.setStats(stats);
      }
      return stats;
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Failed to fetch disk stats';
      if (this.diskStore.currentDisk === targetDisk) {
        this.diskStore.statsError = message;
      }
      return null;
    } finally {
      if (this.diskStore.currentDisk === targetDisk) {
        this.diskStore.isLoadingStats = false;
      }
    }
  }
}

