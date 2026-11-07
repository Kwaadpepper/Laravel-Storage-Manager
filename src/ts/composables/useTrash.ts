import { getApiClient, type ApiClient } from '@ts/clients';
import {
  purgedTrashResponseSchema,
  restoredTrashResponseSchema,
  trashListResponseSchema,
} from '@ts/schemas';
import { useConfigStore, useDiskStore, type ConfigStore, type DiskStore } from '@ts/stores';
import { PurgedTrashResponse, RestoredTrashResponse, TrashListResponse } from '@ts/types';

function resolvePath(urlStr: string): string {
  try {
    return new URL(urlStr).pathname;
  } catch {
    return urlStr;
  }
}

export function useTrash(
  injectedApiClient?: ApiClient,
  injectedConfigStore?: ConfigStore,
  injectedDiskStore?: DiskStore
) {
  const getClient = () => injectedApiClient ?? getApiClient();
  const getConfigStore = () => injectedConfigStore ?? useConfigStore();
  const getDiskStore = () => injectedDiskStore ?? useDiskStore();

  function getDisk(disk?: string): string {
    const d = disk ?? getDiskStore().currentDisk;
    if (!d) {
      throw new Error('No disk selected');
    }
    return d;
  }

  async function list(disk?: string): Promise<TrashListResponse> {
    const targetDisk = getDisk(disk);
    const config = getConfigStore();
    const routeUrl = config.routes?.trashList || '/sm/api/trash/list';
    const path = resolvePath(routeUrl);

    return getClient().get(
      `${path}?disk=${encodeURIComponent(targetDisk)}`,
      trashListResponseSchema
    );
  }

  async function restore(ids: string[], disk?: string): Promise<RestoredTrashResponse> {
    const targetDisk = getDisk(disk);
    const config = getConfigStore();
    const routeUrl = config.routes?.trashRestore || '/sm/api/trash/restore';
    const path = resolvePath(routeUrl);

    return getClient().post(
      path,
      {
        disk: targetDisk,
        ids,
      },
      restoredTrashResponseSchema
    );
  }

  async function purge(ids: string[], disk?: string): Promise<PurgedTrashResponse> {
    const targetDisk = getDisk(disk);
    const config = getConfigStore();
    const routeUrl = config.routes?.trashPurge || '/sm/api/trash/purge';
    const path = resolvePath(routeUrl);

    return getClient().delete(
      path,
      {
        disk: targetDisk,
        ids,
      },
      purgedTrashResponseSchema
    );
  }

  async function empty(disk?: string): Promise<PurgedTrashResponse> {
    const targetDisk = getDisk(disk);
    const config = getConfigStore();
    const routeUrl = config.routes?.trashEmpty || '/sm/api/trash/empty';
    const path = resolvePath(routeUrl);

    return getClient().delete(
      path,
      {
        disk: targetDisk,
      },
      purgedTrashResponseSchema
    );
  }

  async function emptyTrash(disk?: string): Promise<PurgedTrashResponse> {
    return empty(disk);
  }

  return {
    list,
    restore,
    purge,
    empty,
    emptyTrash,
  };
}

export class TrashService {
  private readonly trash: ReturnType<typeof useTrash>;

  constructor(
    apiService?: ApiClient,
    configStore?: ConfigStore,
    diskStore?: DiskStore
  ) {
    this.trash = useTrash(apiService, configStore, diskStore);
  }

  public async list(disk?: string): Promise<TrashListResponse> {
    return this.trash.list(disk);
  }

  public async restore(ids: string[], disk?: string): Promise<RestoredTrashResponse> {
    return this.trash.restore(ids, disk);
  }

  public async purge(ids: string[], disk?: string): Promise<PurgedTrashResponse> {
    return this.trash.purge(ids, disk);
  }

  public async empty(disk?: string): Promise<PurgedTrashResponse> {
    return this.trash.empty(disk);
  }

  public async emptyTrash(disk?: string): Promise<PurgedTrashResponse> {
    return this.trash.emptyTrash(disk);
  }
}
