import { treeResponseSchema } from "@ts/schemas";
import { TreeNodeDirectory, TreeNodeFile } from "@ts/types";
import { ApiService } from "./api-service";

export class FileManagerService {
  private readonly prefix: string = '/sm/fm'

  constructor(private readonly apiService: ApiService) {
    this.apiService = apiService
  }

  async initialize(): Promise<void> {
    return this.apiService.get(`${this.prefix}/init`)
  }

  async listFiles(path: string): Promise<{
    directories: TreeNodeDirectory[],
    files: TreeNodeFile[],
  }> {
    return this.apiService
      .get(`${this.prefix}/tree?disk=public&path=${encodeURIComponent(path)}`, treeResponseSchema)
      .then(data => {
        return {
          directories: data.directories.map(dir => ({
            path: dir.path,
            hasSubDirectories: dir.hasSubDirectories,
          })),
          files: data.files.map(file => ({
            path: file.path,
            size: file.size,
            extension: file.extension,
          })),
        }
      })
  }
}
