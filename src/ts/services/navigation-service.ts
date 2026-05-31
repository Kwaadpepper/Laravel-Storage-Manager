import { NavigationError } from "@ts/errors";
import { useFileManagerStore, useTreeStore } from "@ts/stores";
import { Path, rootPath } from "@ts/types";
import { FileManagerService } from "./file-manager-service";

export enum NavigationEvent {
  NavigateTo = 'navigateTo',
  NavigateUp = 'navigateUp',
  NavigateNext = 'navigateNext',
  NavigatePrevious = 'navigatePrevious',
}

export class NavigationService {
  private readonly navigationHistory: Path[] = []
  private navigationIndex: number = -1
  private loadedPath: Path | null = null
  private readonly treeLoadingPaths = new Set<string>()
  private readonly eventListeners: { [event in NavigationEvent]?: (() => void)[]
  } = {}

  constructor(
    private readonly fileManagerStore: typeof useFileManagerStore,
    private readonly fileManagerService: FileManagerService,
    private readonly treeStore: typeof useTreeStore
  ) {
    this.fileManagerStore = fileManagerStore
    this.fileManagerStore.subscribe(state => {
      if (state.currentPath !== this.loadedPath) {
        this.loadedPath = state.currentPath
        this.fileManagerService.listFiles(state.currentPath).then(({ directories, files }) => {
          this.fileManagerStore.setState({ directories, files })
          this.treeStore.getState().setNodeChildren(state.currentPath, directories)
          this.treeStore.getState().expandAncestors(state.currentPath)
        }).catch(() => {
          throw new NavigationError(`Error navigating to path: ${state.currentPath}`)
        })
      }
    })
  }

  public loadTreeNode(path: Path): void {
    const nodes = this.treeStore.getState().nodes
    if (nodes[path]?.loaded || this.treeLoadingPaths.has(path)) {
      this.treeStore.getState().toggleExpanded(path)
      return
    }
    this.treeLoadingPaths.add(path)
    this.treeStore.getState().toggleExpanded(path)
    this.fileManagerService.listFiles(path).then(({ directories }) => {
      this.treeStore.getState().setNodeChildren(path, directories)
    }).catch(() => {
      throw new NavigationError(`Error loading tree node: ${path}`)
    }).finally(() => {
      this.treeLoadingPaths.delete(path)
    })
  }

  public on(event: NavigationEvent, callback: () => void): void {
    this.eventListeners[event] ??= []
    this.eventListeners[event]?.push(callback)
  }

  private emit(event: NavigationEvent): void {
    this.eventListeners[event]?.forEach(callback => callback())
  }

  public canNavigateUp(): boolean {
    return this.getCurrentPath() !== rootPath()
  }

  public canNavigateNext(): boolean {
    return this.navigationIndex < this.navigationHistory.length - 1
  }

  public canNavigatePrevious(): boolean {
    return this.navigationIndex > 0
  }

  public refreshCurrentPath(): void {
    const currentPath = this.getCurrentPath()
    this.fileManagerService.listFiles(currentPath).then(({ directories, files }) => {
      this.fileManagerStore.setState({ directories, files })
      this.treeStore.getState().setNodeChildren(currentPath, directories)
    }).catch(() => {
      throw new NavigationError(`Error refreshing path: ${currentPath}`)
    })
  }

  public navigateTo(path: Path): void {
    const currentHistoryPath = this.navigationHistory.at(this.navigationIndex)

    if (path === this.getCurrentPath() && currentHistoryPath === path) {
      return
    }

    this.pushHistory(path)
    this.fileManagerStore.setState({ currentPath: path, selectedFile: null })
    this.updateNavigationCapabilities()
    this.emit(NavigationEvent.NavigateTo)
  }

  public navigateToRoot(): void {
    this.navigateTo(rootPath())
  }

  public navigateToParent(): void {
    const currentPath = this.getCurrentPath()

    if (currentPath === rootPath()) {
      return
    }

    const parentPath = currentPath.split('/').slice(0, -1).join('/') || '/'
    this.navigateTo(parentPath as Path)
    this.emit(NavigationEvent.NavigateUp)
  }

  public navigateNext(): void {
    if (!this.canNavigateNext()) {
      return
    }

    const nextPath = this.navigationHistory.at(this.navigationIndex + 1)

    if (!nextPath) {
      return
    }

    this.navigationIndex++
    this.updateUrl(nextPath)
    this.fileManagerStore.setState({ currentPath: nextPath, selectedFile: null })
    this.updateNavigationCapabilities()
    this.emit(NavigationEvent.NavigateNext)
  }

  public navigatePrevious(): void {
    if (!this.canNavigatePrevious()) {
      return
    }

    const previousPath = this.navigationHistory.at(this.navigationIndex - 1)

    if (!previousPath) {
      return
    }

    this.navigationIndex--
    this.updateUrl(previousPath)
    this.fileManagerStore.setState({ currentPath: previousPath, selectedFile: null })
    this.updateNavigationCapabilities()
    this.emit(NavigationEvent.NavigatePrevious)
  }

  private pushHistory(path: Path): void {
    if (this.navigationIndex < this.navigationHistory.length - 1) {
      this.navigationHistory.splice(this.navigationIndex + 1)
    }

    if (this.navigationHistory.at(this.navigationIndex) === path) {
      return
    }

    const url = new URL(globalThis.location.href)
    url.hash = path
    globalThis.history.pushState({}, '', url.toString())
    this.navigationHistory.push(path)
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

  private updateUrl(path: Path): void {
    const url = new URL(globalThis.location.href)
    url.hash = path
    globalThis.history.replaceState({}, '', url.toString())
  }
}
