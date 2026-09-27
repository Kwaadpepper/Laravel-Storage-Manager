import { DiskStore } from '@ts/stores';
import { Path } from '@ts/types';

export class DownloadService {
  private readonly prefix: string = '/sm';

  constructor(
    private readonly diskStore: DiskStore
  ) {}

  getStreamUrl(path: Path): string {
    const disk = this.diskStore.currentDisk ?? 'public';
    const encodedPath = path.split('/').slice(1).map(encodeURIComponent).join('/');
    return `${this.prefix}/stream/${encodeURIComponent(disk)}/${encodedPath}`;
  }

  getDownloadUrl(path: Path): string {
    const disk = this.diskStore.currentDisk ?? 'public';
    const encodedPath = path.split('/').slice(1).map(encodeURIComponent).join('/');
    return `${this.prefix}/download/${encodeURIComponent(disk)}/${encodedPath}`;
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
}
