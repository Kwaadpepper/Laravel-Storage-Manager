import { useConfigStore, useDiskStore, type ConfigStore, type DiskStore } from '@ts/stores';
import { Path } from '@ts/types';

export function buildFileUrl(
  endpoint: 'stream' | 'thumb' | 'download',
  path: Path,
  diskStore?: DiskStore,
  configStore?: ConfigStore
): string {
  const currentDisk = diskStore?.currentDisk ?? 'public';
  const rawPrefix = configStore?.routePrefix || '/sm';
  const prefix = (rawPrefix.startsWith('/') ? rawPrefix : `/${rawPrefix}`).replace(/\/+$/, '');
  const normalizedPath = path.startsWith('/') ? path.slice(1) : path;
  const encodedPath = normalizedPath.split('/').map(encodeURIComponent).join('/');
  return `${prefix}/${endpoint}/${encodeURIComponent(currentDisk)}/${encodedPath}`;
}

export function useDownload(injectedDiskStore?: DiskStore, injectedConfigStore?: ConfigStore) {
  const getDiskStore = () => injectedDiskStore ?? useDiskStore();
  const getConfigStore = () => injectedConfigStore ?? useConfigStore();

  function getStreamUrl(path: Path): string {
    return buildFileUrl('stream', path, getDiskStore(), getConfigStore());
  }

  function getThumbnailUrl(path: Path): string {
    return buildFileUrl('thumb', path, getDiskStore(), getConfigStore());
  }

  function getDownloadUrl(path: Path): string {
    return buildFileUrl('download', path, getDiskStore(), getConfigStore());
  }

  async function downloadFile(path: Path): Promise<Blob> {
    const downloadUrl = getDownloadUrl(path);
    return fetch(downloadUrl).then((response) => {
      if (!response.ok) {
        throw new Error('Failed to download file');
      }
      return response.blob();
    });
  }

  async function triggerDownload(path: Path, fileName: string): Promise<void> {
    const blob = await downloadFile(path);
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = fileName;
    a.click();
    URL.revokeObjectURL(url);
  }

  return {
    getStreamUrl,
    getThumbnailUrl,
    getDownloadUrl,
    downloadFile,
    triggerDownload,
  };
}

export class DownloadService {
  constructor(
    private readonly diskStore: DiskStore,
    private readonly configStore?: ConfigStore
  ) {}

  public getStreamUrl(path: Path): string {
    return buildFileUrl('stream', path, this.diskStore, this.configStore);
  }

  public getThumbnailUrl(path: Path): string {
    return buildFileUrl('thumb', path, this.diskStore, this.configStore);
  }

  public getDownloadUrl(path: Path): string {
    return buildFileUrl('download', path, this.diskStore, this.configStore);
  }

  public async downloadFile(path: Path): Promise<Blob> {
    const downloadUrl = this.getDownloadUrl(path);
    return fetch(downloadUrl).then((response) => {
      if (!response.ok) {
        throw new Error('Failed to download file');
      }
      return response.blob();
    });
  }

  public async triggerDownload(path: Path, fileName: string): Promise<void> {
    const blob = await this.downloadFile(path);
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = fileName;
    a.click();
    URL.revokeObjectURL(url);
  }
}
