import { useContextualMenuRegistration } from "@ts/components/shared/use-contextual-menu-registration";
import { toAnchorName } from "@ts/stores";
import { TreeNodeFile } from "@ts/types";
import { FileIcon } from "lucide-react";
import { useMemo } from "react";

interface ContentFileProps {
  readonly item: TreeNodeFile
}

export default function ContentFile({ item }: Readonly<ContentFileProps>) {
  const anchorName = toAnchorName(item.path)

  const entries = useMemo(() => [
    { label: 'Download', onClick: () => console.log(`Download ${item.path}`) },
    { label: 'Rename', onClick: () => console.log(`Rename ${item.path}`) },
    { label: 'Delete', onClick: () => console.log(`Delete ${item.path}`) },
  ], [item.path])

  useContextualMenuRegistration(anchorName, entries)

  return (
    <>
      <div data-contextual-menu={anchorName} tabIndex={-1} style={{ anchorName }} className="table-cell p-2">
        <span className="flex items-center gap-2">
          <FileIcon size={16} className="text-info" />
          <span>{item.name}</span>
        </span>
      </div>
      <div className="table-cell p-2"><span className="badge badge-ghost badge-sm">File</span></div>
      <div className="table-cell p-2">{item.size} o</div>
      <div className="table-cell p-2">{item.extension ?? <span className="text-base-content/40">N/A</span>}</div>
    </>
  )
}
