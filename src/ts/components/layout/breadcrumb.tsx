import { useContainer } from '@ts/container';
import { useFileManagerStore } from '@ts/stores';
import { Path, rootPath } from '@ts/types';
import { useMemo } from 'react';

interface BreadcrumbViewProps {
}

interface BreadcrumbPathPart {
  readonly name: string
  readonly path: Path
}

export default function Breadcrumb(_: Readonly<BreadcrumbViewProps>) {
  const container = useContainer()
  const navigationService = container.resolve('navigationService')
  const { currentPath } = useFileManagerStore()

  const breadcrumb = useMemo<BreadcrumbPathPart[]>(() => {
    const parts = currentPath.split('/').filter(Boolean)
    const breadcrumbParts: BreadcrumbPathPart[] = []

    breadcrumbParts.push({ name: 'root', path: rootPath() })

    for (const [i, part] of parts.entries()) {
      const path = ('/' + parts.slice(0, i + 1).join('/')) as Path
      breadcrumbParts.push({ name: decodeURIComponent(part), path })
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
