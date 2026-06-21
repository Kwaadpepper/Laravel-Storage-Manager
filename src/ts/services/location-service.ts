import { Disk, Path, rootPath } from "@ts/types";

export class LocationService {

  public getDiskAndPath(): { disk: Disk | null, path: Path } {
    const hash = globalThis.location.hash
    if (hash.startsWith('#/')) {
      const decoded = decodeURIComponent(hash.slice(2))
      if (!decoded) {
        return { disk: null, path: rootPath() }
      }
      const slashIndex = decoded.indexOf('/')
      if (slashIndex === -1) {
        return { disk: decoded as Disk, path: rootPath() }
      } else {
        const disk = decoded.substring(0, slashIndex) as Disk
        let path = decoded.substring(slashIndex) as Path
        if (path === '') path = rootPath()
        return { disk, path }
      }
    }
    return { disk: null, path: rootPath() }
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

  public push(disk: Disk, path: Path): void {
    const url = new URL(globalThis.location.href)
    const hashPath = `/${disk}${path === rootPath() ? '' : path}`
    url.hash = hashPath.split('/').map(encodeURIComponent).join('/')
    globalThis.history.pushState({}, '', url.toString())
  }

  public replace(disk: Disk, path: Path): void {
    const url = new URL(globalThis.location.href)
    const hashPath = `/${disk}${path === rootPath() ? '' : path}`
    url.hash = hashPath.split('/').map(encodeURIComponent).join('/')
    globalThis.history.replaceState({}, '', url.toString())
  }

  public onPopState(callback: (disk: Disk | null, path: Path) => void): () => void {
    const handler = () => {
      const { disk, path } = this.getDiskAndPath()
      callback(disk, path)
    }
    globalThis.addEventListener('popstate', handler)
    return () => globalThis.removeEventListener('popstate', handler)
  }
}
