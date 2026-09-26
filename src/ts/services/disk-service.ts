import { ConfigStore, DiskStore } from '@ts/stores';
import { Disk } from '@ts/types';
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
  }
}
