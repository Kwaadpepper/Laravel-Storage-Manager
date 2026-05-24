import { useContainer } from "@ts/container";
import { useFileManagerStore } from "@ts/stores";
import { Path } from "@ts/types";
import { FolderOpen } from "lucide-react";
import ContentWrapper from "./content/content-wrapper";

type ContentViewProps = {
}

export default function ContentView(_: ContentViewProps) {
  const { directories, files } = useFileManagerStore()
  const container = useContainer()
  const navigationService = container.cradle.navigationService

  function onDirectoryClick(path: Path) {
    navigationService.navigateTo(path)
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

      {directories.length === 0 && files.length === 0 && (
        <div className="flex flex-col items-center justify-center py-16 text-base-content/40">
          <FolderOpen size={48} className="mb-2" />
          <p>Dossier vide</p>
        </div>
      )}
    </div>
  );
}
