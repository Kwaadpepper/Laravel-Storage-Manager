import { Disk, Path, rootPath } from '@ts/types';

export function getDiskAndPath(): { disk: Disk | null; path: Path; searchParams?: URLSearchParams } {
  const hash = globalThis.location?.hash ?? '';
  if (hash.startsWith('#/')) {
    let rawHash = hash.slice(2);
    let searchParams: URLSearchParams | undefined;

    const qIndex = rawHash.indexOf('?');
    if (qIndex !== -1) {
      searchParams = new URLSearchParams(rawHash.slice(qIndex + 1));
      rawHash = rawHash.slice(0, qIndex);
    }

    const decoded = decodeURIComponent(rawHash);
    if (!decoded) {
      return { disk: null, path: rootPath(), searchParams };
    }
    const slashIndex = decoded.indexOf('/');
    if (slashIndex === -1) {
      return { disk: decoded as Disk, path: rootPath(), searchParams };
    } else {
      const disk = decoded.substring(0, slashIndex) as Disk;
      let path = decoded.substring(slashIndex) as Path;
      if (path === '') path = rootPath();
      return { disk, path, searchParams };
    }
  }
  return { disk: null, path: rootPath() };
}

export function getParentPath(path: Path): Path | null {
  if (path === rootPath()) return null;
  return (path.split('/').slice(0, -1).join('/') || '/') as Path;
}

export function getRootPath(): Path {
  return rootPath();
}

export function getAncestorPaths(path: Path): Path[] {
  const parts = path.split('/').filter(Boolean);
  const ancestors: Path[] = [rootPath()];
  for (let i = 1; i < parts.length; i++) {
    ancestors.push(('/' + parts.slice(0, i).join('/')) as Path);
  }
  return ancestors;
}

export function pushLocation(disk: Disk, path: Path, searchParams?: URLSearchParams): void {
  const url = new URL(globalThis.location.href);
  const hashPath = `/${disk}${path === rootPath() ? '' : path}`;
  url.hash =
    hashPath.split('/').map(encodeURIComponent).join('/') +
    (searchParams ? `?${searchParams.toString()}` : '');
  globalThis.history.pushState({}, '', url.toString());
}

export function replaceLocation(disk: Disk, path: Path, searchParams?: URLSearchParams): void {
  const url = new URL(globalThis.location.href);
  const hashPath = `/${disk}${path === rootPath() ? '' : path}`;
  url.hash =
    hashPath.split('/').map(encodeURIComponent).join('/') +
    (searchParams ? `?${searchParams.toString()}` : '');
  globalThis.history.replaceState({}, '', url.toString());
}

export function onPopState(
  callback: (disk: Disk | null, path: Path, searchParams?: URLSearchParams) => void
): () => void {
  const handler = () => {
    const { disk, path, searchParams } = getDiskAndPath();
    callback(disk, path, searchParams);
  };
  globalThis.addEventListener('popstate', handler);
  return () => globalThis.removeEventListener('popstate', handler);
}

export function useLocation() {
  return {
    getDiskAndPath,
    getParentPath,
    getRootPath,
    getAncestorPaths,
    push: pushLocation,
    replace: replaceLocation,
    onPopState,
  };
}

export class LocationService {
  public getDiskAndPath() {
    return getDiskAndPath();
  }

  public getParentPath(path: Path) {
    return getParentPath(path);
  }

  public getRootPath() {
    return getRootPath();
  }

  public getAncestorPaths(path: Path) {
    return getAncestorPaths(path);
  }

  public push(disk: Disk, path: Path, searchParams?: URLSearchParams) {
    pushLocation(disk, path, searchParams);
  }

  public replace(disk: Disk, path: Path, searchParams?: URLSearchParams) {
    replaceLocation(disk, path, searchParams);
  }

  public onPopState(callback: (disk: Disk | null, path: Path, searchParams?: URLSearchParams) => void) {
    return onPopState(callback);
  }
}
