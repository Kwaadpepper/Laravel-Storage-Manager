import {
  purgedTrashResponseSchema,
  restoredTrashResponseSchema,
  trashListResponseSchema,
} from '@ts/schemas';
import { ConfigStore, DiskStore } from '@ts/stores';
import { PurgedTrashResponse, RestoredTrashResponse, TrashListResponse } from '@ts/types';
import { ApiService } from './api-service';

export class TrashService {
  constructor(
    private readonly apiService: ApiService,
    private readonly configStore: ConfigStore,
    private readonly diskStore: DiskStore,
  ) {}

  private getDisk(disk?: string): string {
    const d = disk ?? this.diskStore.currentDisk;
    if (!d) {
      throw new Error('No disk selected');
    }
    return d;
  }

  private resolvePath(urlStr: string): string {
    try {
      return new URL(urlStr).pathname;
    } catch {
      return urlStr;
    }
  }

  async list(disk?: string): Promise<TrashListResponse> {
    const targetDisk = this.getDisk(disk);
    const routeUrl = this.configStore.routes.trashList || '/sm/api/trash/list';
    const path = this.resolvePath(routeUrl);

    return this.apiService.get(
      `${path}?disk=${encodeURIComponent(targetDisk)}`,
      trashListResponseSchema
    );
  }

  async restore(ids: string[], disk?: string): Promise<RestoredTrashResponse> {
    const targetDisk = this.getDisk(disk);
    const routeUrl = this.configStore.routes.trashRestore || '/sm/api/trash/restore';
    const path = this.resolvePath(routeUrl);

    return this.apiService.post(
      path,
      {
        disk: targetDisk,
        ids,
      },
      restoredTrashResponseSchema
    );
  }

  async purge(ids: string[], disk?: string): Promise<PurgedTrashResponse> {
    const targetDisk = this.getDisk(disk);
    const routeUrl = this.configStore.routes.trashPurge || '/sm/api/trash/purge';
    const path = this.resolvePath(routeUrl);

    return this.apiService.delete(
      path,
      {
        disk: targetDisk,
        ids,
      },
      purgedTrashResponseSchema
    );
  }

  async empty(disk?: string): Promise<PurgedTrashResponse> {
    const targetDisk = this.getDisk(disk);
    const routeUrl = this.configStore.routes.trashEmpty || '/sm/api/trash/empty';
    const path = this.resolvePath(routeUrl);

    return this.apiService.delete(
      path,
      {
        disk: targetDisk,
      },
      purgedTrashResponseSchema
    );
  }

  async emptyTrash(disk?: string): Promise<PurgedTrashResponse> {
    return this.empty(disk);
  }
}
