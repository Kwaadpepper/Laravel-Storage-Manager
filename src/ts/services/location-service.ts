import { Path, rootPath } from "@ts/types";

export class LocationService {

  public getCurrentPath(): Path {
    const hash = globalThis.location.hash
    if (hash.startsWith('#/')) {
      return decodeURIComponent(hash.slice(1)) as Path
    }
    return rootPath()
  }

  public getParentPath(path: Path): Path | null {
    if (path === rootPath()) return null
    return (path.split('/').slice(0, -1).join('/') || '/') as Path
  }

  public getRootPath(): Path {
    return rootPath()
  }


  public getAncestorPaths(path: Path): Path[] {
    const parts = path.split('/').filter(Boolean)
    const ancestors: Path[] = [rootPath()]
    for (let i = 1; i < parts.length; i++) {
      ancestors.push(('/' + parts.slice(0, i).join('/')) as Path)
    }
    return ancestors
  }

  public push(path: Path): void {
    const url = new URL(globalThis.location.href)
    url.hash = path
    globalThis.history.pushState({}, '', url.toString())
  }

  public replace(path: Path): void {
    const url = new URL(globalThis.location.href)
    url.hash = path
    globalThis.history.replaceState({}, '', url.toString())
  }

  public onPopState(callback: (path: Path) => void): () => void {
    const handler = () => callback(this.getCurrentPath())
    globalThis.addEventListener('popstate', handler)
    return () => globalThis.removeEventListener('popstate', handler)
  }
}
