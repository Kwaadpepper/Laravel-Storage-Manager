import { Disk, Path, rootPath } from "@ts/types";

export class LocationService {

  public getDiskAndPath(): { disk: Disk | null, path: Path, searchParams?: URLSearchParams } {
    const hash = globalThis.location.hash
    if (hash.startsWith('#/')) {
      let rawHash = hash.slice(2)
      let searchParams: URLSearchParams | undefined
      
      const qIndex = rawHash.indexOf('?')
      if (qIndex !== -1) {
        searchParams = new URLSearchParams(rawHash.slice(qIndex + 1))
        rawHash = rawHash.slice(0, qIndex)
      }

      const decoded = decodeURIComponent(rawHash)
      if (!decoded) {
        return { disk: null, path: rootPath(), searchParams }
      }
      const slashIndex = decoded.indexOf('/')
      if (slashIndex === -1) {
        return { disk: decoded as Disk, path: rootPath(), searchParams }
      } else {
        const disk = decoded.substring(0, slashIndex) as Disk
        let path = decoded.substring(slashIndex) as Path
        if (path === '') path = rootPath()
        return { disk, path, searchParams }
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

  public push(disk: Disk, path: Path, searchParams?: URLSearchParams): void {
    const url = new URL(globalThis.location.href)
    const hashPath = `/${disk}${path === rootPath() ? '' : path}`
    url.hash = hashPath.split('/').map(encodeURIComponent).join('/') + (searchParams ? `?${searchParams.toString()}` : '')
    globalThis.history.pushState({}, '', url.toString())
  }

  public replace(disk: Disk, path: Path, searchParams?: URLSearchParams): void {
    const url = new URL(globalThis.location.href)
    const hashPath = `/${disk}${path === rootPath() ? '' : path}`
    url.hash = hashPath.split('/').map(encodeURIComponent).join('/') + (searchParams ? `?${searchParams.toString()}` : '')
    globalThis.history.replaceState({}, '', url.toString())
  }

  public onPopState(callback: (disk: Disk | null, path: Path, searchParams?: URLSearchParams) => void): () => void {
    const handler = () => {
      const { disk, path, searchParams } = this.getDiskAndPath()
      callback(disk, path, searchParams)
    }
    globalThis.addEventListener('popstate', handler)
    return () => globalThis.removeEventListener('popstate', handler)
  }
}
