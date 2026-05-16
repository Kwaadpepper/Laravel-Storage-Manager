import { useContainer } from '@ts/container';
import { useFileManagerStore } from '@ts/stores';
import { Path, rootPath } from '@ts/types';
import { useMemo } from 'react';

type BreadcrumbViewProps = {
}

type BreadcrumbPathPart = {
  readonly name: string
  readonly path: Path
}

export default function Breadcrumb({ }: Readonly<BreadcrumbViewProps>) {
  const container = useContainer()
  const navigationService = container.cradle.navigationService
  const { currentPath } = useFileManagerStore()

  const breadcrumb = useMemo<BreadcrumbPathPart[]>(() => {
    const url = new URL(globalThis.location.href)
    url.pathname = currentPath
    const parts = url.pathname.split('/').filter(Boolean) as Path[]
    const breadcrumbParts: BreadcrumbPathPart[] = []

    breadcrumbParts.push({ name: 'root', path: rootPath() })

    for (const [i, part] of parts.entries()) {
      url.pathname = parts.slice(0, i + 1).join('/')
      breadcrumbParts.push({ name: part, path: url.pathname as Path })
    }

    return breadcrumbParts
  }, [currentPath])

  function onBreadcrumbClick(part: BreadcrumbPathPart) {
    navigationService.navigateTo(part.path)
  }

  return (
    <div className="breadcrumbs text-sm">
      <ul>
        {breadcrumb.map((part, partIndex) => (
          <li key={`breadcrumb-part-${part.path}-${partIndex}`}>
            <button className="link link-hover" onClick={() => onBreadcrumbClick(part)}>
              {part.name}
            </button>
          </li>
        ))}
      </ul>
    </div>
  );
}
