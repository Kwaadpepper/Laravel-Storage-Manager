import { NavigationError } from "@ts/errors";
import { useFileManagerStore, useTreeStore } from "@ts/stores";
import { Path, TreeNodeDirectory, TreeNodeFile } from "@ts/types";
import { FileManagerService } from "./file-manager-service";
import { LocationService } from "./location-service";

export enum NavigationEvent {
  NavigateTo = 'navigateTo',
  NavigateUp = 'navigateUp',
  NavigateNext = 'navigateNext',
  NavigatePrevious = 'navigatePrevious',
}

export interface PreloadedData {
  directories: TreeNodeDirectory[]
  files: TreeNodeFile[]
}

export class NavigationService {
  private readonly navigationHistory: { disk: import('@ts/types').Disk, path: Path }[] = []
  private navigationIndex: number = -1
  private readonly loadingPaths = new Set<string>()
  private readonly eventListeners: { [event in NavigationEvent]?: (() => void)[]
  } = {}

  constructor(
    private readonly fileManagerStore: typeof useFileManagerStore,
    private readonly fileManagerService: FileManagerService,
    private readonly treeStore: typeof useTreeStore,
    private readonly locationService: LocationService,
    private readonly diskStore: typeof import('@ts/stores').useDiskStore
  ) {
    this.locationService.onPopState((disk, path) => {
      const currentDisk = this.diskStore.getState().currentDisk
      if (disk && disk !== currentDisk) {
        this.diskStore.getState().setCurrentDisk(disk)
        this.treeStore.getState().reset()
      }
      this.commitNavigation(path, NavigationEvent.NavigateTo)
    })
  }

  public on(event: NavigationEvent, callback: () => void): void {
    this.eventListeners[event] ??= []
    this.eventListeners[event]?.push(callback)
  }

  public canNavigateUp(): boolean {
    return this.locationService.getParentPath(this.getCurrentPath()) !== null
  }

  public canNavigateNext(): boolean {
    return this.navigationIndex < this.navigationHistory.length - 1
  }

  public canNavigatePrevious(): boolean {
    return this.navigationIndex > 0
  }

  public navigateTo(path: Path, preloadedData?: PreloadedData): void {
    const currentDisk = this.diskStore.getState().currentDisk
    if (!currentDisk) return
    const currentHistoryEntry = this.navigationHistory.at(this.navigationIndex)
    if (path === this.getCurrentPath() && currentHistoryEntry?.path === path && currentHistoryEntry?.disk === currentDisk) {
      return
    }
    this.pushHistory(currentDisk, path)
    this.fileManagerStore.getState().setCurrentPath(path)
    this.updateNavigationCapabilities()
    this.emit(NavigationEvent.NavigateTo)
    this.fetchAndApply(path, preloadedData).catch(() => {
      throw new NavigationError(`Error navigating to path: ${path}`)
    })
  }

  public navigateToRoot(): void {
    this.navigateTo(this.locationService.getRootPath())
  }

  public switchDisk(): void {
    const root = this.locationService.getRootPath()
    const currentDisk = this.diskStore.getState().currentDisk
    if (!currentDisk) return

    this.treeStore.getState().reset()
    this.fileManagerStore.getState().setCurrentPath(root)
    this.pushHistory(currentDisk, root)
    this.updateNavigationCapabilities()
    this.emit(NavigationEvent.NavigateTo)
    this.fetchAndApply(root).catch(() => {
      throw new NavigationError(`Error navigating to root after disk switch`)
    })
  }

  public navigateToParent(): void {
    const parentPath = this.locationService.getParentPath(this.getCurrentPath())
    if (parentPath === null) return
    const currentDisk = this.diskStore.getState().currentDisk
    if (!currentDisk) return
    this.pushHistory(currentDisk, parentPath)
    this.commitNavigation(parentPath, NavigationEvent.NavigateUp)
  }

  public navigateNext(): void {
    if (!this.canNavigateNext()) return
    this.navigateToHistoryIndex(1, NavigationEvent.NavigateNext)
  }

  public navigatePrevious(): void {
    if (!this.canNavigatePrevious()) return
    this.navigateToHistoryIndex(-1, NavigationEvent.NavigatePrevious)
  }

  public refreshCurrentPath(): void {
    const currentPath = this.getCurrentPath()
    this.fetchAndApply(currentPath).catch(() => {
      throw new NavigationError(`Error refreshing path: ${currentPath}`)
    })
  }

  public loadTreeNode(path: Path): void {
    if (this.treeStore.getState().nodes[path]?.loaded || this.loadingPaths.has(path)) {
      this.treeStore.getState().toggleExpanded(path)
      return
    }
    this.loadingPaths.add(path)
    this.treeStore.getState().toggleExpanded(path)
    this.fileManagerService.listFiles(path)
      .then(({ directories }) => {
        this.treeStore.getState().setNodeChildren(path, directories)
      })
      .catch(() => {
        throw new NavigationError(`Error loading tree node: ${path}`)
      })
      .finally(() => { this.loadingPaths.delete(path) })
  }

  private async fetchAndApply(path: Path, data?: PreloadedData): Promise<void> {
    const { directories, files } = data ?? await this.fileManagerService.listFiles(path)
    this.fileManagerStore.setState({ directoryNodes: directories, fileNodes: files })
    this.treeStore.getState().setNodeChildren(path, directories)
    this.treeStore.getState().expandAncestors(path)
    this.loadAncestorsForTree(path)
  }

  private loadAncestorsForTree(path: Path): void {
    for (const ancestor of this.locationService.getAncestorPaths(path)) {
      if (this.treeStore.getState().nodes[ancestor]?.loaded || this.loadingPaths.has(ancestor)) {
        continue
      }
      this.loadingPaths.add(ancestor)
      this.fileManagerService.listFiles(ancestor)
        .then(({ directories }) => {
          this.treeStore.getState().setNodeChildren(ancestor, directories)
        })
        .catch(() => { /* silent - tree just won't show children */ })
        .finally(() => { this.loadingPaths.delete(ancestor) })
    }
  }

  private commitNavigation(path: Path, event: NavigationEvent): void {
    this.fileManagerStore.getState().setCurrentPath(path)
    this.updateNavigationCapabilities()
    this.emit(event)
    this.fetchAndApply(path).catch(() => {
      throw new NavigationError(`Error navigating to path: ${path}`)
    })
  }

  private navigateToHistoryIndex(offset: 1 | -1, event: NavigationEvent): void {
    const targetEntry = this.navigationHistory.at(this.navigationIndex + offset)
    if (!targetEntry) return
    this.navigationIndex += offset
    
    const { disk, path } = targetEntry
    
    this.locationService.replace(disk, path)
    
    const currentDisk = this.diskStore.getState().currentDisk
    if (disk !== currentDisk) {
      this.diskStore.getState().setCurrentDisk(disk)
      this.treeStore.getState().reset()
    }
    
    this.commitNavigation(path, event)
  }

  private emit(event: NavigationEvent): void {
    this.eventListeners[event]?.forEach(callback => callback())
  }

  private pushHistory(disk: import('@ts/types').Disk, path: Path): void {
    if (this.navigationIndex < this.navigationHistory.length - 1) {
      this.navigationHistory.splice(this.navigationIndex + 1)
    }
    const currentEntry = this.navigationHistory.at(this.navigationIndex)
    if (currentEntry?.disk === disk && currentEntry?.path === path) {
      return
    }
    this.locationService.push(disk, path)
    this.navigationHistory.push({ disk, path })
    this.navigationIndex = this.navigationHistory.length - 1
  }

  private updateNavigationCapabilities(): void {
    this.fileManagerStore.setState({
      canNavigatePrevious: this.canNavigatePrevious(),
      canNavigateNext: this.canNavigateNext(),
      canNavigateUp: this.canNavigateUp(),
    })
  }

  private getCurrentPath(): Path {
    return this.fileManagerStore.getState().currentPath
  }
}
