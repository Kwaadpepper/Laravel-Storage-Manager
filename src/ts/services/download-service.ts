import { Path } from "@ts/types";

export class DownloadService {
  private readonly prefix: string = '/sm'

  constructor(
    private readonly diskStore: typeof import('@ts/stores').useDiskStore
  ) {}

  async downloadFile(path: Path): Promise<Blob> {
    const disk = this.diskStore.getState().currentDisk ?? 'public'
    const encodedPath = path.split('/').slice(1).map(encodeURIComponent).join('/')
    return fetch(`${this.prefix}/download/${encodeURIComponent(disk)}/${encodedPath}`)
      .then(response => {
        if (!response.ok) {
          throw new Error('Failed to download file')
        }
        return response.blob()
      })
  }
}
