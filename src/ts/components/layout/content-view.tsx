import { useFileManagerStore, useUiStore } from "@ts/stores";
import { FolderOpen } from "lucide-react";
import ContentTileDirectory from "./content/content-tile-directory";
import ContentTileFile from "./content/content-tile-file";
import ContentWrapper from "./content/content-wrapper";

interface ContentViewProps {
}

export default function ContentView(_: Readonly<ContentViewProps>) {
  const { directories, files } = useFileManagerStore()
  const { viewMode } = useUiStore()

  const isEmpty = directories.length === 0 && files.length === 0

  if (viewMode === 'tiles') {
    return (
      <div className="p-2">
        {isEmpty ? (
          <div className="flex flex-col items-center justify-center py-16 text-base-content/40">
            <FolderOpen size={48} className="mb-2" />
            <p>Dossier vide</p>
          </div>
        ) : (
          <div className="flex flex-wrap gap-2">
            {directories.map((d) => (
              <ContentTileDirectory key={d.path} item={d} />
            ))}
            {files.map((f) => (
              <ContentTileFile key={f.path} item={f} />
            ))}
          </div>
        )}
      </div>
    )
  }

  return (
    <div className="overflow-x-auto">
      <div className="table table-zebra w-full">
        <div className="table-header-group">
          <div className="table-row">
            <div className="table-cell p-2">Name</div>
            <div className="table-cell p-2">Type</div>
            <div className="table-cell p-2">Size</div>
            <div className="table-cell p-2">Extension</div>
          </div>
        </div>
        <div className="table-row-group">
          {directories.map((d) => (
            <ContentWrapper key={d.path} item={d} />
          ))}
          {files.map((f) => (
            <ContentWrapper key={f.path} item={f} />
          ))}
        </div>
      </div>

      {isEmpty && (
        <div className="flex flex-col items-center justify-center py-16 text-base-content/40">
          <FolderOpen size={48} className="mb-2" />
          <p>Dossier vide</p>
        </div>
      )}
    </div>
  );
}
