import { isValidationError, ValidationError } from "@ts/errors";
import { treeResponseSchema } from "@ts/schemas";
import { Path, TreeNodeDirectory, TreeNodeFile } from "@ts/types";
import { ApiService } from "./api-service";

export type CreateDirectoryValidationField = 'directoryName'
export type RenameDirectoryValidationField = 'directoryName'

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
      disk: 'public',
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
    return this.apiService.delete(`${this.prefix}/delete`, {
      disk: 'public',
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
