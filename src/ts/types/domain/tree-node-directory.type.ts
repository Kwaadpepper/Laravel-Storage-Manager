import { Path, TreeNode } from '@ts/types';

export interface TreeNodeDirectory extends TreeNode {
  path: Path
  name: string
  hasSubDirectories: boolean
  visibility: 'public' | 'private' | null
}

export function isDirectory(node: TreeNode): node is TreeNodeDirectory {
  return (node as TreeNodeDirectory).hasSubDirectories !== undefined
}
