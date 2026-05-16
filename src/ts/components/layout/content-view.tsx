import { useContainer } from "@ts/container";
import { useFileManagerStore } from "@ts/stores";
import { Path, rootPath } from "@ts/types";
import { File as FileIcon, Folder, FolderOpen } from "lucide-react";

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
      <table className="table table-zebra w-full">
        <thead>
          <tr>
            <th>Nom</th>
            <th>Type</th>
            <th>Taille</th>
            <th>Extension</th>
          </tr>
        </thead>
        <tbody>
          {directories.map((d) => (
            <tr key={d.path} className="hover cursor-pointer" onClick={() => onDirectoryClick(d.path)}>
              <td>
                <span className="flex items-center gap-2">
                  <Folder size={16} className="text-warning" />
                  {d.path === rootPath() ? 'root' : d.path}
                </span>
              </td>
              <td><span className="badge badge-ghost badge-sm">Dossier</span></td>
              <td>—</td>
              <td>—</td>
            </tr>
          ))}
          {files.map((f) => (
            <tr key={f.path}>
              <td>
                <span className="flex items-center gap-2">
                  <FileIcon size={16} className="text-info" />
                  {f.path}
                </span>
              </td>
              <td><span className="badge badge-ghost badge-sm">Fichier</span></td>
              <td>{f.size} o</td>
              <td>{f.extension ?? <span className="text-base-content/40">N/A</span>}</td>
            </tr>
          ))}
        </tbody>
      </table>

      {directories.length === 0 && files.length === 0 && (
        <div className="flex flex-col items-center justify-center py-16 text-base-content/40">
          <FolderOpen size={48} className="mb-2" />
          <p>Dossier vide</p>
        </div>
      )}
    </div>
  );
}
