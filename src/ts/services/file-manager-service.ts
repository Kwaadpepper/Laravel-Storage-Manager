import { isValidationError, ValidationError } from "@ts/errors";
import { existsResponseSchema, treeResponseSchema } from "@ts/schemas";
import { useDiskStore } from "@ts/stores";
import { Path, TreeNodeDirectory, TreeNodeFile } from "@ts/types";
import { ApiService } from "./api-service";

export type CreateDirectoryValidationField = 'directoryName'
export type RenameDirectoryValidationField = 'directoryName'
export type RenameFileValidationField = 'fileName'

export class FileManagerService {
  private readonly prefix: string = '/sm/fm'

  constructor(
    private readonly apiService: ApiService,
    private readonly diskStore: typeof useDiskStore,
  ) { }

  private getDisk(): string {
    return this.diskStore.getState().currentDisk ?? 'public'
  }

  async initialize(): Promise<void> {
    return this.apiService.get(`${this.prefix}/init`)
  }

  async listFiles(path: Path): Promise<{
    directories: TreeNodeDirectory[],
    files: TreeNodeFile[],
  }> {
    return this.apiService
      .get(`${this.prefix}/tree?disk=${encodeURIComponent(this.getDisk())}&path=${encodeURIComponent(path)}`, treeResponseSchema)
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
      disk: this.getDisk(),
      path,
      name,
    }).catch((error: unknown) => {
      if (isValidationError(error)) {
        throw this.remapValidationError(error, {
          name: 'directoryName',
        })
      }

      throw error
    })
  }

  async renameDirectory(path: Path, newName: string): Promise<void> {
    return this.apiService.put(`${this.prefix}/rename`, {
      disk: this.getDisk(),
      path,
      to: newName,
    }).catch((error: unknown) => {
      if (isValidationError(error)) {
        throw this.remapValidationError(error, {
          to: 'directoryName',
        })
      }

      throw error
    })
  }

  async deleteDirectory(path: Path): Promise<void> {
    return this.deleteAtPath(path)
  }

  async copy(path: Path, destinationDir: Path): Promise<void> {
    return this.apiService.post(`${this.prefix}/copy`, {
      disk: this.getDisk(),
      path,
      destination_dir: destinationDir,
    })
  }

  async move(path: Path, destinationDir: Path): Promise<void> {
    return this.apiService.post(`${this.prefix}/move`, {
      disk: this.getDisk(),
      path,
      destination_dir: destinationDir,
    })
  }

  async createFile(path: Path, name: string, content: string = ''): Promise<void> {
    return this.apiService.post(`${this.prefix}/create-file`, {
      disk: this.getDisk(),
      path,
      name,
      content,
    }).catch((error: unknown) => {
      if (isValidationError(error)) {
        throw this.remapValidationError(error, {
          name: 'fileName',
        })
      }

      throw error
    })
  }

  async fileExists(path: Path): Promise<boolean> {
    return this.apiService
      .get(`${this.prefix}/exists?disk=${encodeURIComponent(this.getDisk())}&path=${encodeURIComponent(path)}`, existsResponseSchema)
      .then(data => data.exists)
  }

  async renameFile(path: Path, newName: string): Promise<void> {
    return this.apiService.put(`${this.prefix}/rename`, {
      disk: this.getDisk(),
      path,
      to: newName,
    }).catch((error: unknown) => {
      if (isValidationError(error)) {
        throw this.remapValidationError(error, {
          to: 'fileName',
        })
      }

      throw error
    })
  }

  async deleteFile(path: Path): Promise<void> {
    return this.deleteAtPath(path)
  }

  private async deleteAtPath(path: Path): Promise<void> {
    return this.apiService.delete(`${this.prefix}/delete`, {
      disk: this.getDisk(),
      path,
    })
  }

  private remapValidationError<TFieldName extends string>(
    error: ValidationError,
    fieldMap: Record<string, TFieldName>
  ): ValidationError<TFieldName> {
    const mappedErrors = {} as Record<TFieldName, string>

    for (const [fieldName, message] of Object.entries(error.getFieldErrors())) {
      const mappedFieldName = fieldMap[fieldName]

      if (mappedFieldName) {
        mappedErrors[mappedFieldName] = message
      }
    }

    return new ValidationError(mappedErrors, error.message)
  }
}
