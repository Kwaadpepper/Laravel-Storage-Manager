import { useConfigStore, useDiskStore } from '@ts/stores';
import { Disk } from '@ts/types';
import { ApiService } from './api-service';

export class DiskService {
  constructor(
    private readonly apiService: ApiService,
    private readonly configStore: typeof useConfigStore,
    private readonly diskStore: typeof useDiskStore,
  ) { }

  async selectDisk(disk: Disk): Promise<void> {
    const url = this.configStore.getState().routes.disksSelect
    const path = new URL(url).pathname
    await this.apiService.post(path, { disk })
    this.diskStore.getState().setCurrentDisk(disk)
  }
}
