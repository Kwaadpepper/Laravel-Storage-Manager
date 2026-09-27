import { isDomainValidationError, isValidationError, ValidationError } from "@ts/errors";
import { existsResponseSchema, propertiesResponseSchema, searchResponseSchema, treeResponseSchema } from "@ts/schemas";
import { DiskStore } from "@ts/stores";
import { FileMetadata, Path, TreeNodeDirectory, TreeNodeFile } from "@ts/types";
import { ApiService } from "./api-service";

export type CreateDirectoryValidationField = 'directoryName';
export type CreateFileValidationField = 'fileName';
export type RenameDirectoryValidationField = 'directoryName';
export type RenameFileValidationField = 'fileName';

export class FileManagerService {
  private readonly prefix: string = '/sm/fm';

  constructor(
    private readonly apiService: ApiService,
    private readonly diskStore: DiskStore,
  ) {}

  private getDisk(): string {
    return this.diskStore.currentDisk ?? 'public';
  }

  async initialize(): Promise<void> {
    return this.apiService.get(`${this.prefix}/init`);
  }

  async listFiles(path: Path): Promise<{
    directories: TreeNodeDirectory[];
    files: TreeNodeFile[];
  }> {
    return this.apiService
      .get(`${this.prefix}/tree?disk=${encodeURIComponent(this.getDisk())}&path=${encodeURIComponent(path)}`, treeResponseSchema)
      .then(data => {
        return {
          directories: data.directories.map(dir => ({
            path: dir.path,
            name: dir.path.split('/').pop() || '',
            hasSubDirectories: dir.hasSubDirectories,
            visibility: dir.visibility,
          })),
          files: data.files.map(file => ({
            path: file.path,
            name: file.path.split('/').pop() || '',
            size: file.size,
            extension: file.extension,
            visibility: file.visibility,
            publicUrl: file.publicUrl,
          })),
        };
      });
  }

  async search(paths: Path[], query?: string, extension?: string, minSize?: number, maxSize?: number): Promise<{
    directoriesToScan: Path[];
    matchedDirectories: import('@ts/types').TreeNodeDirectory[];
    matchedFiles: import('@ts/types').TreeNodeFile[];
  }> {
    return this.apiService
      .post(`${this.prefix}/search`, {
        disk: this.getDisk(),
        paths,
        ...(query ? { query } : {}),
        ...(extension ? { extension } : {}),
        ...(minSize ? { minSize } : {}),
        ...(maxSize ? { maxSize } : {}),
      }, searchResponseSchema)
      .then(data => {
        return {
          directoriesToScan: data.directoriesToScan,
          matchedDirectories: data.matchedDirectories.map(dir => ({
            path: dir.path,
            name: dir.path.split('/').pop() || '',
            hasSubDirectories: dir.hasSubDirectories,
            visibility: dir.visibility,
          })),
          matchedFiles: data.matchedFiles.map(file => ({
            path: file.path,
            name: file.path.split('/').pop() || '',
            size: file.size,
            extension: file.extension,
            visibility: file.visibility,
            publicUrl: file.publicUrl,
          })),
        };
      });
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
        });
      }

      if (isDomainValidationError(error) && error.getDomainCode() === 1001) {
        throw new ValidationError<CreateDirectoryValidationField>(
          { directoryName: error.message },
          error.message
        );
      }

      throw error;
    });
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
        });
      }

      if (isDomainValidationError(error) && error.getDomainCode() === 1001) {
        throw new ValidationError<RenameDirectoryValidationField>(
          { directoryName: error.message },
          error.message
        );
      }

      throw error;
    });
  }

  async deleteDirectory(path: Path, force: boolean = false): Promise<void> {
    return this.deleteItem(path, force);
  }

  async copy(path: Path, destinationDir: Path): Promise<void> {
    return this.apiService.post(`${this.prefix}/copy`, {
      disk: this.getDisk(),
      path,
      destination_dir: destinationDir,
    });
  }

  async move(path: Path, destinationDir: Path): Promise<void> {
    return this.apiService.post(`${this.prefix}/move`, {
      disk: this.getDisk(),
      path,
      destination_dir: destinationDir,
    });
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
        });
      }

      if (isDomainValidationError(error) && error.getDomainCode() === 1003) {
        throw new ValidationError<CreateFileValidationField>(
          { fileName: error.message },
          error.message
        );
      }

      throw error;
    });
  }

  async fileExists(path: Path): Promise<boolean> {
    return this.apiService
      .get(`${this.prefix}/exists?disk=${encodeURIComponent(this.getDisk())}&path=${encodeURIComponent(path)}`, existsResponseSchema)
      .then(data => data.exists);
  }

  async getFileProperties(path: Path): Promise<FileMetadata> {
    return this.apiService
      .get(`${this.prefix}/properties?disk=${encodeURIComponent(this.getDisk())}&path=${encodeURIComponent(path)}`, propertiesResponseSchema)
      .then((data) => ({
        path: data.path,
        basename: data.basename,
        dirname: data.dirname,
        timestamp: data.timestamp,
        visibility: data.visibility ?? null,
        isFile: data.isFile ?? true,
        isDirectory: data.isDirectory ?? false,
        extension: data.extension ?? null,
        size: data.size ?? null,
        mimeType: data.mimeType ?? null,
        publicUrl: data.publicUrl ?? null,
        checksum: data.checksum ?? null,
        dimensions: data.dimensions ?? null,
        exif: data.exif
          ? {
              make: data.exif.make ?? null,
              model: data.exif.model ?? null,
              lens: data.exif.lens ?? null,
              aperture: data.exif.aperture ?? null,
              exposureTime: data.exif.exposureTime ?? null,
              iso: data.exif.iso ?? null,
              focalLength: data.exif.focalLength ?? null,
              focalLengthIn35mm: data.exif.focalLengthIn35mm ?? null,
              flash: data.exif.flash ?? null,
              whiteBalance: data.exif.whiteBalance ?? null,
              meteringMode: data.exif.meteringMode ?? null,
              dateTaken: data.exif.dateTaken ?? null,
              gps: data.exif.gps
                ? {
                    latitude: data.exif.gps.latitude,
                    longitude: data.exif.gps.longitude,
                    altitude: data.exif.gps.altitude ?? null,
                    mapsUrl: data.exif.gps.mapsUrl,
                    googleMapsUrl: data.exif.gps.googleMapsUrl,
                  }
                : null,
              software: data.exif.software ?? null,
              artist: data.exif.artist ?? null,
              copyright: data.exif.copyright ?? null,
            }
          : null,
        rawExif: data.rawExif ?? null,
      }));
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
        });
      }

      if (isDomainValidationError(error) && error.getDomainCode() === 1003) {
        throw new ValidationError<RenameFileValidationField>(
          { fileName: error.message },
          error.message
        );
      }

      throw error;
    });
  }

  async deleteFile(path: Path): Promise<void> {
    return this.deleteItem(path);
  }

  public async deleteItem(path: Path, force: boolean = false): Promise<void> {
    return this.apiService.delete(`${this.prefix}/delete`, {
      disk: this.getDisk(),
      path,
      force,
    });
  }

  async bulkRename(renames: Array<{ from: Path; to: string }>): Promise<void> {
    return this.apiService.put(`${this.prefix}/bulk-rename`, {
      disk: this.getDisk(),
      renames,
    });
  }

  async zip(paths: Path[], destination: string): Promise<void> {
    return this.apiService.post(`${this.prefix}/zip`, {
      disk: this.getDisk(),
      paths,
      destination,
    });
  }

  async unzip(path: Path, destinationDir?: Path): Promise<void> {
    return this.apiService.post(`${this.prefix}/unzip`, {
      disk: this.getDisk(),
      path,
      ...(destinationDir ? { destinationDir } : {}),
    });
  }

  private remapValidationError<TFieldName extends string>(
    error: ValidationError,
    fieldMap: Record<string, TFieldName>
  ): ValidationError<TFieldName> {
    const mappedErrors = {} as Record<TFieldName, string>;

    for (const [fieldName, message] of Object.entries(error.getFieldErrors())) {
      const mappedFieldName = fieldMap[fieldName];

      if (mappedFieldName) {
        mappedErrors[mappedFieldName] = message;
      }
    }

    return new ValidationError(mappedErrors, error.message);
  }
}
