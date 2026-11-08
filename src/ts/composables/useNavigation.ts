import { NavigationError } from '@ts/errors';
import {
  useConfigStore,
  useDiskStore,
  useFileManagerStore,
  useTreeStore,
  type ConfigStore,
  type DiskStore,
  type FileManagerStore,
  type TreeStore,
} from '@ts/stores';
import { Disk, Path, TreeNodeDirectory, TreeNodeFile } from '@ts/types';
import { useFileManager, type FileManagerService } from './useFileManager';
import {
  getAncestorPaths,
  getParentPath,
  getRootPath,
  onPopState,
  pushLocation,
  replaceLocation,
} from './useLocation';

export enum NavigationEvent {
  NavigateTo = 'navigateTo',
  NavigateUp = 'navigateUp',
  NavigateNext = 'navigateNext',
  NavigatePrevious = 'navigatePrevious',
}

export interface PreloadedData {
  directories: TreeNodeDirectory[];
  files: TreeNodeFile[];
}

export interface NavigationHistoryEntry {
  disk: Disk;
  path: Path;
  searchParams?: URLSearchParams;
}

// Module-level state to maintain shared navigation history
const navigationHistory: NavigationHistoryEntry[] = [];
let navigationIndex: number = -1;
const loadingPaths = new Set<string>();
const eventListeners: { [event in NavigationEvent]?: (() => void)[] } = {};
let popStateInitialized = false;
let activeSortBy: string | undefined = undefined;
let activeSortOrder: 'asc' | 'desc' | undefined = undefined;

function initPopState(
  diskStore: DiskStore,
  treeStore: TreeStore,
  fileManagerStore: FileManagerStore,
  configStore: ConfigStore,
  fileManager: ReturnType<typeof useFileManager>
) {
  if (popStateInitialized) return;
  popStateInitialized = true;

  onPopState((disk, path, searchParams) => {
    const currentDisk = diskStore.currentDisk;
    if (disk && disk !== currentDisk) {
      diskStore.setCurrentDisk(disk);
      treeStore.reset();
    }
    commitNavigation(path, NavigationEvent.NavigateTo, searchParams, fileManagerStore, diskStore, configStore, treeStore, fileManager);
  });
}

function emit(event: NavigationEvent): void {
  eventListeners[event]?.forEach((callback) => callback());
}

function updateDocumentTitle(path: Path, diskStore: DiskStore, configStore: ConfigStore): void {
  const currentDisk = diskStore.currentDisk;
  if (!currentDisk) return;
  const packageName = configStore.packageName || 'Storage Manager';
  const folderName = path === '/' ? currentDisk : path.split('/').pop();
  if (typeof document !== 'undefined') {
    document.title = `${folderName} - ${currentDisk} - ${packageName}`;
  }
}

function updateNavigationCapabilities(fileManagerStore: FileManagerStore): void {
  fileManagerStore.setNavigationCapabilities({
    canNavigatePrevious: canNavigatePrevious(),
    canNavigateNext: canNavigateNext(),
    canNavigateUp: canNavigateUp(fileManagerStore.currentPath),
  });
}

export function canNavigateUp(currentPath?: Path): boolean {
  const path = currentPath ?? useFileManagerStore().currentPath;
  return getParentPath(path) !== null;
}

export function canNavigateNext(): boolean {
  return navigationIndex < navigationHistory.length - 1;
}

export function canNavigatePrevious(): boolean {
  return navigationIndex > 0;
}

function pushHistory(disk: Disk, path: Path, searchParams?: URLSearchParams): void {
  if (navigationIndex < navigationHistory.length - 1) {
    navigationHistory.splice(navigationIndex + 1);
  }
  const currentEntry = navigationHistory.at(navigationIndex);
  if (
    currentEntry?.disk === disk &&
    currentEntry?.path === path &&
    currentEntry?.searchParams?.toString() === searchParams?.toString()
  ) {
    return;
  }
  pushLocation(disk, path, searchParams);
  navigationHistory.push({ disk, path, searchParams });
  navigationIndex = navigationHistory.length - 1;
}

async function fetchAndApply(
  path: Path,
  fileManagerStore: FileManagerStore,
  treeStore: TreeStore,
  fileManager: ReturnType<typeof useFileManager>,
  data?: PreloadedData,
  sortBy?: string,
  sortOrder?: 'asc' | 'desc'
): Promise<void> {
  if (path === '/:search') {
    fileManagerStore.setDirectoryNodes([]);
    fileManagerStore.setFileNodes([]);
    return;
  }

  const { directories, files, pagination } =
    data
      ? { ...data, pagination: undefined }
      : (await fileManager.listFiles(path, 1, 50, sortBy, sortOrder));
  fileManagerStore.setDirectoryNodes(directories);
  fileManagerStore.setFileNodes(files, pagination);
  treeStore.setNodeChildren(path, directories);
  treeStore.expandAncestors(path);
  loadAncestorsForTree(path, treeStore, fileManager);
}

function loadAncestorsForTree(
  path: Path,
  treeStore: TreeStore,
  fileManager: ReturnType<typeof useFileManager>
): void {
  for (const ancestor of getAncestorPaths(path)) {
    if (treeStore.nodes[ancestor]?.loaded || loadingPaths.has(ancestor)) {
      continue;
    }
    loadingPaths.add(ancestor);
    fileManager
      .listFiles(ancestor)
      .then(({ directories }) => {
        treeStore.setNodeChildren(ancestor, directories);
      })
      .catch(() => {
        /* silent */
      })
      .finally(() => {
        loadingPaths.delete(ancestor);
      });
  }
}

function commitNavigation(
  path: Path,
  event: NavigationEvent,
  searchParams: URLSearchParams | undefined,
  fileManagerStore: FileManagerStore,
  diskStore: DiskStore,
  configStore: ConfigStore,
  treeStore: TreeStore,
  fileManager: ReturnType<typeof useFileManager>
): void {
  fileManagerStore.setCurrentPath(path, searchParams);
  updateNavigationCapabilities(fileManagerStore);
  updateDocumentTitle(path, diskStore, configStore);
  emit(event);
  fetchAndApply(path, fileManagerStore, treeStore, fileManager, undefined, activeSortBy, activeSortOrder).catch(() => {
    throw new NavigationError(`Error navigating to path: ${path}`);
  });
}

export function useNavigation() {
  const fileManagerStore = useFileManagerStore();
  const treeStore = useTreeStore();
  const diskStore = useDiskStore();
  const configStore = useConfigStore();
  const fileManager = useFileManager();

  initPopState(diskStore, treeStore, fileManagerStore, configStore, fileManager);

  function on(event: NavigationEvent, callback: () => void): void {
    eventListeners[event] ??= [];
    eventListeners[event]?.push(callback);
  }

  function navigateTo(path: Path, preloadedData?: PreloadedData, searchParams?: URLSearchParams): void {
    const currentDisk = diskStore.currentDisk;
    if (!currentDisk) return;
    const currentHistoryEntry = navigationHistory.at(navigationIndex);
    if (
      path === fileManagerStore.currentPath &&
      currentHistoryEntry?.path === path &&
      currentHistoryEntry?.disk === currentDisk &&
      currentHistoryEntry?.searchParams?.toString() === searchParams?.toString()
    ) {
      return;
    }
    pushHistory(currentDisk, path, searchParams);
    fileManagerStore.setCurrentPath(path, searchParams);
    updateNavigationCapabilities(fileManagerStore);
    updateDocumentTitle(path, diskStore, configStore);
    emit(NavigationEvent.NavigateTo);
    fetchAndApply(path, fileManagerStore, treeStore, fileManager, preloadedData, activeSortBy, activeSortOrder).catch(() => {
      throw new NavigationError(`Error navigating to path: ${path}`);
    });
  }

  function navigateToRoot(): void {
    navigateTo(getRootPath());
  }

  function switchDisk(): void {
    const root = getRootPath();
    const currentDisk = diskStore.currentDisk;
    if (!currentDisk) return;

    treeStore.reset();
    fileManagerStore.setCurrentPath(root);
    pushHistory(currentDisk, root);
    updateNavigationCapabilities(fileManagerStore);
    updateDocumentTitle(root, diskStore, configStore);
    emit(NavigationEvent.NavigateTo);
    fetchAndApply(root, fileManagerStore, treeStore, fileManager, undefined, activeSortBy, activeSortOrder).catch(() => {
      throw new NavigationError('Error navigating to root after disk switch');
    });
  }

  function navigateToParent(): void {
    const parentPath = getParentPath(fileManagerStore.currentPath);
    if (parentPath === null) return;
    const currentDisk = diskStore.currentDisk;
    if (!currentDisk) return;
    pushHistory(currentDisk, parentPath);
    commitNavigation(
      parentPath,
      NavigationEvent.NavigateUp,
      undefined,
      fileManagerStore,
      diskStore,
      configStore,
      treeStore,
      fileManager
    );
  }

  function navigateToHistoryIndex(offset: 1 | -1, event: NavigationEvent): void {
    const targetEntry = navigationHistory.at(navigationIndex + offset);
    if (!targetEntry) return;
    navigationIndex += offset;

    const { disk, path, searchParams } = targetEntry;

    replaceLocation(disk, path, searchParams);

    const currentDisk = diskStore.currentDisk;
    if (disk !== currentDisk) {
      diskStore.setCurrentDisk(disk);
      treeStore.reset();
    }

    commitNavigation(
      path,
      event,
      searchParams,
      fileManagerStore,
      diskStore,
      configStore,
      treeStore,
      fileManager
    );
  }

  function navigateNext(): void {
    if (!canNavigateNext()) return;
    navigateToHistoryIndex(1, NavigationEvent.NavigateNext);
  }

  function navigatePrevious(): void {
    if (!canNavigatePrevious()) return;
    navigateToHistoryIndex(-1, NavigationEvent.NavigatePrevious);
  }

  async function refreshCurrentPath(): Promise<void> {
    const currentPath = fileManagerStore.currentPath;
    try {
      await fetchAndApply(
        currentPath,
        fileManagerStore,
        treeStore,
        fileManager,
        undefined,
        activeSortBy,
        activeSortOrder
      );
    } catch {
      throw new NavigationError(`Error refreshing path: ${currentPath}`);
    }
  }

  async function loadMoreFiles(): Promise<void> {
    if (fileManagerStore.isLoadingMore) return;
    const pagination = fileManagerStore.pagination;
    if (!pagination || !pagination.hasMore) return;

    const currentPath = fileManagerStore.currentPath;
    if (currentPath === '/:search') return;

    fileManagerStore.setIsLoadingMore(true);
    try {
      const nextPage = pagination.currentPage + 1;
      const { files, pagination: newMeta } = await fileManager.listFiles(
        currentPath,
        nextPage,
        pagination.perPage,
        activeSortBy,
        activeSortOrder
      );
      fileManagerStore.appendFileNodes(files, newMeta);
    } catch {
      /* silent */
    } finally {
      fileManagerStore.setIsLoadingMore(false);
    }
  }

  async function setSort(sortBy?: string, sortOrder?: 'asc' | 'desc'): Promise<void> {
    activeSortBy = sortBy;
    activeSortOrder = sortOrder;
    await fetchAndApply(
      fileManagerStore.currentPath,
      fileManagerStore,
      treeStore,
      fileManager,
      undefined,
      activeSortBy,
      activeSortOrder
    );
  }

  function loadTreeNode(path: Path): void {
    if (treeStore.nodes[path]?.loaded || loadingPaths.has(path)) {
      treeStore.toggleExpanded(path);
      return;
    }
    loadingPaths.add(path);
    treeStore.toggleExpanded(path);
    fileManager
      .listFiles(path)
      .then(({ directories }) => {
        treeStore.setNodeChildren(path, directories);
      })
      .catch(() => {
        throw new NavigationError(`Error loading tree node: ${path}`);
      })
      .finally(() => {
        loadingPaths.delete(path);
      });
  }

  return {
    canNavigateUp: () => canNavigateUp(fileManagerStore.currentPath),
    canNavigateNext,
    canNavigatePrevious,
    navigateTo,
    navigateToRoot,
    switchDisk,
    navigateToParent,
    navigateNext,
    navigatePrevious,
    refreshCurrentPath,
    loadMoreFiles,
    setSort,
    loadTreeNode,
    on,
  };
}

export class NavigationService {
  private readonly nav: ReturnType<typeof useNavigation>;

  constructor(
    _fileManagerStore?: FileManagerStore,
    _fileManagerService?: FileManagerService,
    _treeStore?: TreeStore,
    _locationService?: unknown,
    _diskStore?: DiskStore,
    _configStore?: ConfigStore
  ) {
    this.nav = useNavigation();
  }

  public on(event: NavigationEvent, callback: () => void) {
    this.nav.on(event, callback);
  }

  public canNavigateUp() {
    return this.nav.canNavigateUp();
  }

  public canNavigateNext() {
    return this.nav.canNavigateNext();
  }

  public canNavigatePrevious() {
    return this.nav.canNavigatePrevious();
  }

  public navigateTo(path: Path, preloadedData?: PreloadedData, searchParams?: URLSearchParams) {
    this.nav.navigateTo(path, preloadedData, searchParams);
  }

  public navigateToRoot() {
    this.nav.navigateToRoot();
  }

  public switchDisk() {
    this.nav.switchDisk();
  }

  public navigateToParent() {
    this.nav.navigateToParent();
  }

  public navigateNext() {
    this.nav.navigateNext();
  }

  public navigatePrevious() {
    this.nav.navigatePrevious();
  }

  public refreshCurrentPath(): Promise<void> {
    return this.nav.refreshCurrentPath();
  }

  public loadMoreFiles(): Promise<void> {
    return this.nav.loadMoreFiles();
  }

  public setSort(sortBy?: string, sortOrder?: 'asc' | 'desc'): Promise<void> {
    return this.nav.setSort(sortBy, sortOrder);
  }

  public loadTreeNode(path: Path) {
    this.nav.loadTreeNode(path);
  }
}
