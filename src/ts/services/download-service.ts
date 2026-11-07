import { ConfigStore, DiskStore } from '@ts/stores';
import { Path } from '@ts/types';

export class DownloadService {
  private get prefix(): string {
    const raw = this.configStore?.routePrefix || '/sm';
    return (raw.startsWith('/') ? raw : `/${raw}`).replace(/\/+$/, '');
  }

  constructor(
    private readonly diskStore: DiskStore,
    private readonly configStore?: ConfigStore,
  ) {}

  private buildFileUrl(endpoint: 'stream' | 'thumb' | 'download', path: Path): string {
    const disk = this.diskStore.currentDisk ?? 'public';
    const encodedPath = path.split('/').slice(1).map(encodeURIComponent).join('/');
    return `${this.prefix}/${endpoint}/${encodeURIComponent(disk)}/${encodedPath}`;
  }

  getStreamUrl(path: Path): string {
    return this.buildFileUrl('stream', path);
  }

  getThumbnailUrl(path: Path): string {
    return this.buildFileUrl('thumb', path);
  }

  getDownloadUrl(path: Path): string {
    return this.buildFileUrl('download', path);
  }

  async downloadFile(path: Path): Promise<Blob> {
    const downloadUrl = this.getDownloadUrl(path);
    return fetch(downloadUrl)
      .then((response) => {
        if (!response.ok) {
          throw new Error('Failed to download file');
        }
        return response.blob();
      });
  }

  async triggerDownload(path: Path, fileName: string): Promise<void> {
    const blob = await this.downloadFile(path);
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = fileName;
    a.click();
    URL.revokeObjectURL(url);
  }
}
