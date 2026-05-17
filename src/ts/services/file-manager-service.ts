import { treeResponseSchema } from "@ts/schemas";
import { Path, TreeNodeDirectory, TreeNodeFile } from "@ts/types";
import { ApiService } from "./api-service";

export class FileManagerService {
  private readonly prefix: string = '/sm/fm'

  constructor(private readonly apiService: ApiService) {
    this.apiService = apiService
  }

  async initialize(): Promise<void> {
    return this.apiService.get(`${this.prefix}/init`)
  }

  async listFiles(path: Path): Promise<{
    directories: TreeNodeDirectory[],
    files: TreeNodeFile[],
  }> {
    return this.apiService
      .get(`${this.prefix}/tree?disk=public&path=${encodeURIComponent(path)}`, treeResponseSchema)
      .then(data => {
        return {
          directories: data.directories.map(dir => ({
            path: dir.path,
            name: dir.path.split('/').pop() || '',
            hasSubDirectories: dir.hasSubDirectories,
          })),
          files: data.files.map(file => ({
            path: file.path,
            name: file.path.split('/').pop() || '',
            size: file.size,
            extension: file.extension,
          })),
        }
      })
  }

  async createDirectory(path: Path, name: string): Promise<void> {
    return this.apiService.post(`${this.prefix}/create-directory`, {
      disk: 'public',
      path,
      name,
    })
  }
}
