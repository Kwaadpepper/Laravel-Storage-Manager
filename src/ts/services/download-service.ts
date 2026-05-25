import { Path } from "@ts/types";

export class DownloadService {
  private readonly prefix: string = '/sm'

  async downloadFile(path: Path): Promise<Blob> {
    return fetch(`${this.prefix}/download/public/${encodeURIComponent(path.split('/').slice(1).join('/'))}`)
      .then(response => {
        if (!response.ok) {
          throw new Error('Failed to download file')
        }
        return response.blob()
      })
  }
}
