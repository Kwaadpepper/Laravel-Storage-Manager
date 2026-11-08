import { getApiClient, type ApiClient } from '@ts/clients';
import {
  isDomainValidationError,
  isValidationError,
  ValidationError,
} from '@ts/errors';
import {
  deletedResponseSchema,
  existsResponseSchema,
  propertiesResponseSchema,
  searchResponseSchema,
  treeResponseSchema,
} from '@ts/schemas';
import { useDiskStore, type DiskStore } from '@ts/stores';
import { FileMetadata, PaginationMeta, Path, TreeNodeDirectory, TreeNodeFile } from '@ts/types';

export type CreateDirectoryValidationField = 'directoryName';
export type CreateFileValidationField = 'fileName';
export type RenameDirectoryValidationField = 'directoryName';
export type RenameFileValidationField = 'fileName';

function remapValidationError<TFieldName extends string>(
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

export function useFileManager(injectedApiClient?: ApiClient, injectedDiskStore?: DiskStore) {
  const prefix = '/sm/fm';
  const getClient = () => injectedApiClient ?? getApiClient();
  const getDisk = () => {
    const diskStore = injectedDiskStore ?? useDiskStore();
    return diskStore.currentDisk ?? 'public';
  };

  async function initialize(): Promise<void> {
    return getClient().get(`${prefix}/init`);
  }

  async function listFiles(
    path: Path,
    page: number = 1,
    perPage: number = 50,
    sortBy?: string,
    sortOrder?: 'asc' | 'desc'
  ): Promise<{
    directories: TreeNodeDirectory[];
    files: TreeNodeFile[];
    pagination?: PaginationMeta;
  }> {
    const params = new URLSearchParams({
      disk: getDisk(),
      path,
      page: String(page),
      per_page: String(perPage),
    });
    if (sortBy) {
      params.set('sort_by', sortBy);
    }
    if (sortOrder) {
      params.set('sort_order', sortOrder);
    }

    return getClient()
      .get(
        `${prefix}/content?${params.toString()}`,
        treeResponseSchema
      )
      .then((data) => ({
        directories: data.directories.map((dir) => ({
          path: dir.path,
          name: dir.path.split('/').pop() || '',
          hasSubDirectories: dir.hasSubDirectories,
          visibility: dir.visibility,
        })),
        files: data.files.map((file) => ({
          path: file.path,
          name: file.path.split('/').pop() || '',
          size: file.size,
          extension: file.extension,
          visibility: file.visibility,
          publicUrl: file.publicUrl,
        })),
        pagination: data.pagination,
      }));
  }

  async function search(
    paths: Path[],
    query?: string,
    extension?: string,
    minSize?: number,
    maxSize?: number
  ): Promise<{
    directoriesToScan: Path[];
    matchedDirectories: TreeNodeDirectory[];
    matchedFiles: TreeNodeFile[];
  }> {
    return getClient()
      .post(
        `${prefix}/search`,
        {
          disk: getDisk(),
          paths,
          ...(query ? { query } : {}),
          ...(extension ? { extension } : {}),
          ...(minSize ? { minSize } : {}),
          ...(maxSize ? { maxSize } : {}),
        },
        searchResponseSchema
      )
      .then((data) => ({
        directoriesToScan: data.directoriesToScan,
        matchedDirectories: data.matchedDirectories.map((dir) => ({
          path: dir.path,
          name: dir.path.split('/').pop() || '',
          hasSubDirectories: dir.hasSubDirectories,
          visibility: dir.visibility,
        })),
        matchedFiles: data.matchedFiles.map((file) => ({
          path: file.path,
          name: file.path.split('/').pop() || '',
          size: file.size,
          extension: file.extension,
          visibility: file.visibility,
          publicUrl: file.publicUrl,
        })),
      }));
  }

  async function createDirectory(path: Path, name: string): Promise<void> {
    return getClient()
      .post(`${prefix}/create-directory`, {
        disk: getDisk(),
        path,
        name,
      })
      .catch((error: unknown) => {
        if (isValidationError(error)) {
          throw remapValidationError(error, {
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

  async function renameDirectory(path: Path, newName: string): Promise<void> {
    return getClient()
      .put(`${prefix}/rename`, {
        disk: getDisk(),
        path,
        to: newName,
      })
      .catch((error: unknown) => {
        if (isValidationError(error)) {
          throw remapValidationError(error, {
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

  async function deleteDirectory(path: Path, force: boolean = false): Promise<void> {
    await deleteItem(path, force);
  }

  async function copy(path: Path, destinationDir: Path): Promise<void> {
    return getClient().post(`${prefix}/copy`, {
      disk: getDisk(),
      path,
      destination_dir: destinationDir,
    });
  }

  async function move(path: Path, destinationDir: Path): Promise<void> {
    return getClient().post(`${prefix}/move`, {
      disk: getDisk(),
      path,
      destination_dir: destinationDir,
    });
  }

  async function createFile(path: Path, name: string, content: string = ''): Promise<void> {
    return getClient()
      .post(`${prefix}/create-file`, {
        disk: getDisk(),
        path,
        name,
        content,
      })
      .catch((error: unknown) => {
        if (isValidationError(error)) {
          throw remapValidationError(error, {
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

  async function fileExists(path: Path): Promise<boolean> {
    return getClient()
      .get(
        `${prefix}/exists?disk=${encodeURIComponent(getDisk())}&path=${encodeURIComponent(path)}`,
        existsResponseSchema
      )
      .then((data) => data.exists);
  }

  async function getFileProperties(path: Path): Promise<FileMetadata> {
    return getClient()
      .get(
        `${prefix}/properties?disk=${encodeURIComponent(getDisk())}&path=${encodeURIComponent(path)}`,
        propertiesResponseSchema
      )
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

  async function renameFile(path: Path, newName: string): Promise<void> {
    return getClient()
      .put(`${prefix}/rename`, {
        disk: getDisk(),
        path,
        to: newName,
      })
      .catch((error: unknown) => {
        if (isValidationError(error)) {
          throw remapValidationError(error, {
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

  async function deleteFile(path: Path): Promise<void> {
    await deleteItem(path);
  }

  async function deleteItem(
    path: Path,
    force: boolean = false
  ): Promise<{ trashed?: boolean; trashId?: string | null } | void> {
    if (force) {
      return getClient().delete(`${prefix}/delete`, {
        disk: getDisk(),
        path,
        force: true,
      });
    }

    return getClient().delete(
      `${prefix}/delete`,
      {
        disk: getDisk(),
        path,
        force: false,
      },
      deletedResponseSchema
    );
  }

  async function bulkRename(renames: Array<{ from: Path; to: string }>): Promise<void> {
    return getClient().put(`${prefix}/bulk-rename`, {
      disk: getDisk(),
      renames,
    });
  }

  async function zip(paths: Path[], destination: string): Promise<void> {
    return getClient().post(`${prefix}/zip`, {
      disk: getDisk(),
      paths,
      destination,
    });
  }

  async function unzip(path: Path, destinationDir?: Path): Promise<void> {
    return getClient().post(`${prefix}/unzip`, {
      disk: getDisk(),
      path,
      ...(destinationDir ? { destinationDir } : {}),
    });
  }

  return {
    initialize,
    listFiles,
    search,
    createDirectory,
    renameDirectory,
    deleteDirectory,
    copy,
    move,
    createFile,
    fileExists,
    getFileProperties,
    renameFile,
    deleteFile,
    deleteItem,
    bulkRename,
    zip,
    unzip,
  };
}

export class FileManagerService {
  private readonly impl: ReturnType<typeof useFileManager>;

  constructor(apiService?: ApiClient, diskStore?: DiskStore) {
    this.impl = useFileManager(apiService, diskStore);
  }

  initialize() {
    return this.impl.initialize();
  }

  listFiles(
    path: Path,
    page: number = 1,
    perPage: number = 50,
    sortBy?: string,
    sortOrder?: 'asc' | 'desc'
  ) {
    return this.impl.listFiles(path, page, perPage, sortBy, sortOrder);
  }

  search(
    paths: Path[],
    query?: string,
    extension?: string,
    minSize?: number,
    maxSize?: number
  ) {
    return this.impl.search(paths, query, extension, minSize, maxSize);
  }

  createDirectory(path: Path, name: string) {
    return this.impl.createDirectory(path, name);
  }

  renameDirectory(path: Path, newName: string) {
    return this.impl.renameDirectory(path, newName);
  }

  deleteDirectory(path: Path, force: boolean = false) {
    return this.impl.deleteDirectory(path, force);
  }

  copy(path: Path, destinationDir: Path) {
    return this.impl.copy(path, destinationDir);
  }

  move(path: Path, destinationDir: Path) {
    return this.impl.move(path, destinationDir);
  }

  createFile(path: Path, name: string, content: string = '') {
    return this.impl.createFile(path, name, content);
  }

  fileExists(path: Path) {
    return this.impl.fileExists(path);
  }

  getFileProperties(path: Path) {
    return this.impl.getFileProperties(path);
  }

  renameFile(path: Path, newName: string) {
    return this.impl.renameFile(path, newName);
  }

  deleteFile(path: Path) {
    return this.impl.deleteFile(path);
  }

  deleteItem(path: Path, force: boolean = false) {
    return this.impl.deleteItem(path, force);
  }

  bulkRename(renames: Array<{ from: Path; to: string }>) {
    return this.impl.bulkRename(renames);
  }

  zip(paths: Path[], destination: string) {
    return this.impl.zip(paths, destination);
  }

  unzip(path: Path, destinationDir?: Path) {
    return this.impl.unzip(path, destinationDir);
  }
}
