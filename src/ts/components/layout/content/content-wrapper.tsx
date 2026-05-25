import { useContainer } from "@ts/container";
import { isDirectory, TreeNodeDirectory, TreeNodeFile } from "@ts/types";
import ContentDirectory from "./content-directory";
import ContentFile from "./content-file";

type Item = TreeNodeDirectory | TreeNodeFile

interface ContentWrapperProps {
  readonly item: Item
}

export default function ContentWrapper({ item }: Readonly<ContentWrapperProps>) {
  const container = useContainer()
  const navigationService = container.resolve('navigationService')

  function onItemClick(item: Item) {
    if (isDirectory(item)) {
      navigationService.navigateTo(item.path)
    } else {
      // onFileClick(item.path)
    }
  }

  return (
    <div
      className="table-row hover:cursor-pointer hover:bg-accent/30"
      title={item.name}
      role="row"
      tabIndex={0}
      onClick={() => onItemClick(item)}
    >
      {isDirectory(item) ? (
        <ContentDirectory item={item} />
      ) : (
        <ContentFile item={item} />
      )}
    </div>
  )
}
