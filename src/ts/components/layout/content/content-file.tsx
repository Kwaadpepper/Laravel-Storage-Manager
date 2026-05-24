import { TreeNodeFile } from "@ts/types";
import { FileIcon } from "lucide-react";

type ContentFileProps = {
  readonly item: TreeNodeFile
}

export default function ContentFile({ item }: ContentFileProps) {

  return (
    <>
      <div className="table-cell p-2">
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
