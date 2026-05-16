import { useFileManagerStore } from "@ts/stores";
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

  private readonly eventListeners: { [event in NavigationEvent]?: (() => void)[]
  } = {}

  constructor(
    private readonly fileManagerStore: typeof useFileManagerStore,
    private readonly fileManagerService: FileManagerService
  ) {
    this.fileManagerStore = fileManagerStore
    this.fileManagerStore.subscribe(state => {
      if (state.currentPath !== this.loadedPath) {
        this.loadedPath = state.currentPath
        this.fileManagerService.listFiles(state.currentPath).then(({ directories, files }) => {
          this.fileManagerStore.setState({ directories, files })
        }).catch(() => {
          console.error('Error navigating to path:', state.currentPath)
        })
      }
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



  public navigateTo(path: Path): void {
    const currentHistoryPath = this.navigationHistory.at(this.navigationIndex)

    if (path === this.getCurrentPath() && currentHistoryPath === path) {
      return
    }

    this.pushHistory(path)
    this.fileManagerStore.setState({ currentPath: path })
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
    this.fileManagerStore.setState({ currentPath: nextPath })
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
    this.fileManagerStore.setState({ currentPath: previousPath })
    this.emit(NavigationEvent.NavigatePrevious)
  }

  private pushHistory(path: Path): void {
    // When navigating after going back, remove the "forward" branch.
    if (this.navigationIndex < this.navigationHistory.length - 1) {
      this.navigationHistory.splice(this.navigationIndex + 1)
    }

    // Prevent duplicate consecutive entries.
    if (this.navigationHistory.at(this.navigationIndex) === path) {
      return
    }

    const url = new URL(globalThis.location.href)
    url.hash = path
    globalThis.history.pushState({}, '', url.toString())
    this.navigationHistory.push(path)
    this.navigationIndex = this.navigationHistory.length - 1
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
